# Instruction: Authenticated Device Pairing — admin.pozarnipoplach.cz

## Overview

This document describes **all changes required in the `admin.pozarnipoplach.cz` project** to implement
authenticated, user-scoped device pairing for alarm.pozarnipoplach.cz kiosk devices.

The new flow replaces the open `/activate` page (anyone can link any device to any unit) with a
**login-gated page** at `admin.pozarnipoplach.cz/activate/XXXXXXXX`. The user must be
logged in, and can only assign units that are explicitly assigned to them in the database.

---

## Security Problem Being Solved

| Current (insecure)                                           | New (secure)                                                      |
|--------------------------------------------------------------|-------------------------------------------------------------------|
| `alarm.pozarnipoplach.cz/activate` is public — no login     | Activation redirects to `admin.pozarnipoplach.cz/activate/XXXX`  |
| Any visitor can link a device to any unit                    | Only authenticated admin users can perform activation              |
| No audit log of who activated which device                   | `activated_by_user_id` stored in `alarm_device_session`           |
| All units always shown in dropdown                           | Only units assigned to the logged-in user are shown               |

---

## New Flow (end-to-end)

```
[Kiosk device] -> /api/auth/device/init  -> shows QR + link to admin.pozarnipoplach.cz/activate/XXXX
[User's phone]  -> opens link -> admin.pozarnipoplach.cz/activate/XXXX
[admin]         -> if not logged in -> redirect to /login (saves /activate/XXXX target)
[admin]         -> after login -> redirected back to /activate/XXXX
[admin]         -> shows list of units assigned to this user only
[User]          -> picks unit name, submits
[admin]         -> calls DeviceAuth::linkSessionToUnit(), stores activated_by_user_id
[Kiosk]         -> polls /api/auth/device/poll -> status=linked -> calls /api/auth/device/authorize -> gets token
```

---

## Step 1 — Database Changes

### 1a. Add `activated_by_user_id` to `alarm_device_session`

```sql
ALTER TABLE `alarm_device_session`
    ADD `activated_by_user_id` INT(10) UNSIGNED NULL DEFAULT NULL
        AFTER `device_name`;
```

### 1b. `user2unit` table — already exists, no migration needed

The database already contains a `user2unit` table with this structure:

| Column    | Type                        | Notes                     |
|-----------|-----------------------------|---------------------------|
| `user_id` | `smallint(5) unsigned`      | PK, FK → `user.id`        |
| `unit_id` | `smallint(5) unsigned`      | PK, FK → `unit.id`        |
| `role`    | `enum('admin','reader')`    | Default: `admin`          |

This table is used directly in `User::getUnitsForUser()` — no SQL migration needed for it.

> NOTE: Users with `status = 'admin'` in the `user` table bypass the `user2unit` lookup and see all active units (enforced in PHP).

---

## Step 2 — Changes to `include/class.DeviceAuth.php`

### 2a. Update `linkSessionToUnit()` to accept and store `$userId`

Change the signature:
```php
public function linkSessionToUnit(
    string $deviceCode,
    int $unitId,
    ?string $deviceName = null,
    ?int $activatedByUserId = null   // NEW parameter
): bool
```

Update the SQL to also set `activated_by_user_id`:
```sql
UPDATE alarm_device_session
SET status = "linked",
    unit_id = <unitId>,
    device_name = "<deviceName>",
    activated_by_user_id = <activatedByUserId>
WHERE device_code = "<deviceCode>"
  AND status = "pending"
  AND expires_at > NOW()
```

---

## Step 3 — Add `getUnitsForUser()` to `include/class.User.php`

No new class needed. Add the following public method to the existing `User` class (e.g. after `getUser()`):

```php
/**
 * Returns units available to this user for device assignment.
 * Users with status='admin' see all active units.
 * All other users see only units assigned via the user2unit table.
 *
 * @return array List of ['id', 'fullname'] rows.
 */
public function getUnitsForUser(): array
{
    $userId     = (int)($this->user['id'] ?? 0);
    $userStatus = $this->user['status'] ?? '';

    if ($userStatus === 'admin') {
        $result = $this->DB->getAllRows($this->DB->query(
            "SELECT id, fullname FROM unit WHERE status = 'ok' ORDER BY fullname ASC",
            __METHOD__
        ));
    } else {
        $result = $this->DB->getAllRows($this->DB->query(
            "SELECT u.id, u.fullname
             FROM unit u
             JOIN user2unit uu ON uu.unit_id = u.id
             WHERE uu.user_id = " . $userId . "
               AND u.status = 'ok'
             ORDER BY u.fullname ASC",
            __METHOD__
        ));
    }

    return $result ?: [];
}
```

> The method reads `$this->user` which is already populated after `$User->load($userId)` — no extra parameters needed.

---

## Step 4 — New Routes in `include/routes.php`

Add before the dashboard catch-all route (around line 210):

```php
# Device Activation - with code in path (e.g. /activate/ABCD1234)
$router->match('GET|POST', '/activate/([A-Za-z0-9]{4,16})', function ($code) use ($Smarty, $DB, $User, $CASBIN) {
    $APPD = AppData::getInstance();
    $APPD->setData('PAGE', 'activate');
    $APPD->setData('DEVICE_CODE', $code);
    include('./view/page/activate.php');
});

# Device Activation - without code in path (manual entry or fallback)
$router->match('GET|POST', '/activate', function () use ($Smarty, $DB, $User, $CASBIN) {
    $APPD = AppData::getInstance();
    $APPD->setData('PAGE', 'activate');
    include('./view/page/activate.php');
});
```

---

## Step 5 — New `view/page/activate.php`

This controller handles the activation page. Key responsibilities:

1. Retrieve code from `$APPD->getData('DEVICE_CODE')`, `$_GET['code']`, or `$_POST['code']`.
2. Require login — if not logged in, store the target URL `/activate/XXXX` in `$_SESSION['login_redirect']` and redirect to `/login`.
3. Validate session using `DeviceAuth::checkSessionStatus($code)` — must be `status=pending`.
4. Load user's units using `$User->getUnitsForUser()`.
5. Handle POST — validate CSRF, validate `unit_id` is in allowed list, call `DeviceAuth::linkSessionToUnit()`.
6. Show success or error.

```php
<?php

use Janmensik\Jmlib\AppData;

$APPD = AppData::getInstance();
$APPD->setData('PAGE', 'activate');

$device_code = $APPD->getData('DEVICE_CODE') ?? $_GET['code'] ?? $_POST['code'] ?? null;
if (!empty($device_code)) {
    $device_code = strtoupper(trim($device_code));
}

// 1. Auth guard
if (!$User->getUser()) {
    $redirectTarget = '/activate' . (!empty($device_code) ? '/' . urlencode($device_code) : '');
    $_SESSION['login_redirect'] = $redirectTarget;
    header('Location: ' . $APPD->getData('BASE_URL') . '/login');
    header('Connection: close');
    exit();
}

$currentUser = $User->getUser();

require_once(__DIR__ . '/../../include/class.DeviceAuth.php');
$DeviceAuth = new \PozarniPoplach\DeviceAuth($DB);

// 2. CSRF init
if (empty($_SESSION['csrf_token_device'])) {
    $_SESSION['csrf_token_device'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_device'];

$error = null;
$session = null;

if (!empty($device_code)) {
    $session = $DeviceAuth->checkSessionStatus($device_code);
    if (!$session || $session['status'] !== 'pending') {
        $error = 'Neplatný nebo prošlý aktivační kód.';
        $session = null;
    }
}

// 3. Load allowed units via User class
$units = $User->getUnitsForUser();

// 4. Handle POST
if ($session && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token_device']) || !hash_equals($_SESSION['csrf_token_device'], (string)$_POST['csrf_token_device'])) {
        $error = 'Neplatný bezpečnostní token (CSRF). Zkuste to prosím znovu.';
    } elseif (!empty($_POST['unit_id'])) {
        $unitId = intval($_POST['unit_id']);
        $allowedIds = array_column($units, 'id');
        if (!in_array($unitId, array_map('intval', $allowedIds), true)) {
            $error = 'Tato jednotka vám není přiřazena.';
        } else {
            if ($DeviceAuth->linkSessionToUnit($device_code, $unitId, $_POST['device_name'] ?? null, (int)$currentUser['id'])) {
                session_regenerate_id(true);
                $Smarty->assign('success', true);
            } else {
                $error = 'Nepodařilo se autorizovat zařízení.';
            }
        }
    }
}

$Smarty->assign('units', $units);
$Smarty->assign('device_code', $device_code);
$Smarty->assign('session_data', $session);
$Smarty->assign('error', $error);
$Smarty->assign('current_user', $currentUser);
$Smarty->assign('csrf_token_device', $csrf_token);

// Note: index.php automatically renders page.activate.html and assigns global template variables
```

---

## Step 6 — New `tpl/page.activate.html`

The template uses Bootstrap 4.6 + AdminLTE v3 + Alpine.js (no jQuery for dynamic behaviour).
Use the AdminLTE "login-page" body class for a standalone centred layout — this avoids needing
the full sidebar while keeping a consistent look.

Key UI elements:
- Logo + "Aktivace výjezdového panelu" heading.
- Code display — shows the 8-char device code for user confirmation.
- Unit dropdown — populated with `$units` (user's allowed units only).
- Device name input — optional friendly name.
- Submit button — "Autorizovat zařízení".
- Success state — green alert + "Okno můžete zavřít."
- Error alert-danger for error state.
- Logged-in user info + logout link in footer.

```html
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivace panelu — Požární poplach Admin</title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/9a1439ba27.js" crossorigin="anonymous"></script>
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" />
    <link rel="stylesheet" href="{$BASE_URL}/ui/application.css" />
</head>
<body class="hold-transition login-page">

<div class="login-box" style="width:480px">
    <div class="login-logo">
        <strong>🔥 Požární poplach</strong>
    </div>
    <div class="card card-outline card-danger">
        <div class="card-header text-center">
            <p class="mb-0 text-muted">Aktivace výjezdového panelu</p>
        </div>
        <div class="card-body">

            {if $success}
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                    <strong>Zařízení bylo úspěšně autorizováno!</strong><br>
                    <small>Na monitoru by se nyní měla zobrazit data.<br>Toto okno můžete zavřít.</small>
                </div>

            {elseif $session_data}
                {if $error}
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{$error|escape}
                    </div>
                {/if}

                <p class="text-muted mb-1">Přihlášen jako: <strong>{$current_user.name|escape}</strong></p>
                <p class="text-muted">
                    Kód zařízení: <code class="h5 text-dark">{$device_code|escape}</code>
                </p>

                <form method="post" action="{$BASE_URL}/activate{if $device_code}/{$device_code|escape}{/if}">
                    <input type="hidden" name="csrf_token_device" value="{$csrf_token_device}">
                    <input type="hidden" name="code" value="{$device_code|escape}">

                    <div class="form-group">
                        <label>Název zařízení <small class="text-muted">(volitelné)</small></label>
                        <input type="text" name="device_name" class="form-control"
                               placeholder="např. Garáž, Kuchyň, Serverovna…">
                    </div>

                    <div class="form-group">
                        <label>Přiřadit k jednotce <span class="text-danger">*</span></label>
                        <select name="unit_id" class="form-control" required {if !$units}disabled{/if}>
                            <option value="" disabled selected>-- Vyberte jednotku --</option>
                            {foreach from=$units item=unit}
                                <option value="{$unit.id|escape}">{$unit.fullname|escape}</option>
                            {/foreach}
                        </select>
                        {if !$units}
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Nemáte přiřazenu žádnou jednotku. Kontaktujte administrátora.
                            </small>
                        {/if}
                    </div>

                    <button type="submit" class="btn btn-danger btn-block" {if !$units}disabled{/if}>
                        <i class="fas fa-shield-alt mr-1"></i> Autorizovat zařízení
                    </button>
                </form>

            {else}
                {if $error}
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{$error|escape}
                    </div>
                {/if}
                <div class="text-center text-muted py-4">
                    <i class="fas fa-qrcode fa-3x mb-3 d-block"></i>
                    <p>Prosím naskenujte QR kód na výjezdovém panelu nebo otevřete odkaz s platným aktivačním kódem.</p>
                    <a href="{$BASE_URL}" class="btn btn-outline-secondary btn-sm mt-2">
                        <i class="fas fa-arrow-left mr-1"></i> Zpět do administrace
                    </a>
                </div>
            {/if}

        </div>
        <div class="card-footer text-muted text-center" style="font-size:0.8rem">
            Přihlášen jako {$current_user.email|escape} ·
            <a href="{$BASE_URL}/logout" class="text-danger">Odhlásit</a>
        </div>
    </div>
</div>

</body>
</html>
```

---

## Step 7 — Update Login Redirect Logic

### 7a. In `include/routes.php` — GET `/login` route

Capture the redirect parameter and store it in session so it survives the POST:

```php
$router->get('/login', function () use ($Smarty, $DB, $CASBIN) {
    $APPD = AppData::getInstance();
    $APPD->setData('PAGE', 'login');

    // Store redirect target so it survives the POST redirect
    if (!empty($_GET['redirect'])) {
        $_SESSION['login_redirect'] = '/' . ltrim(urldecode($_GET['redirect']), '/');
    }
});
```

### 7b. In `view/page/login.php` — after successful login

In the existing file, after `$_SESSION['user_id'] = $user_id;` (line 58), the file immediately
redirects to `BASE_URL` on line 63. **Replace that final redirect block** (lines 60-65) with:

```php
$APPD->MESSAGES['saved']['login'] = 'logged';
$APPD->hibernateMessages();

// Honour redirect-after-login (e.g. /activate/XXXX)
$redirectAfterLogin = $_SESSION['login_redirect'] ?? null;
unset($_SESSION['login_redirect']);
if ($redirectAfterLogin && str_starts_with($redirectAfterLogin, '/')) {
    header('Location: ' . $APPD->getData('BASE_URL') . $redirectAfterLogin);
} else {
    header('Location: ' . $APPD->getData('BASE_URL'));
}
header('Connection: close');
return;
```

> This replaces the existing `$APPD->MESSAGES['saved']['login']`, `hibernateMessages()`, and `header('Location:…')` block — not just inserts after it.

---

## Step 8 — `.env` / `.env.example`

No change needed in admin's `.env` for this feature directly.

However, document in `.env.example` that the alarm project must set `ADMIN_URL`:

```dotenv
# (No new variable needed in admin — see alarm project .env for ADMIN_URL)
```

---

## Step 9 — ACL Policy Update (`include/acl.policy.csv`)

Add `activate` permissions for roles that should be allowed to activate devices:

```csv
p, admin,   activate, read,   allow
p, admin,   activate, write,  allow
p, manager, activate, read,   allow
p, manager, activate, write,  allow
```

Review `index.php` to confirm how ACL is enforced globally — if it only wraps page controllers
but `activate.php` has its own auth guard (Step 5), the ACL entries may not be strictly
necessary for functionality, but are good practice.

---

## Step 10 — Optional Future: Unit Assignment UI

The `user2unit` table is already used for access control elsewhere. To manage user-to-unit
assignments via the admin UI, add a multi-select checkbox panel to the User edit page
(`tpl/page.settings.html` / `view/page/settings.php`). This is a post-MVP enhancement.
For now, manage `user2unit` assignments via phpMyAdmin or direct SQL.

---

## File Change Summary

| File | Action |
|---|---|
| `changes.sql` | Add `ALTER TABLE alarm_device_session ADD activated_by_user_id…` only (no new table needed) |
| `include/class.DeviceAuth.php` | Update `linkSessionToUnit()` — add `$activatedByUserId` param + SQL |
| `include/class.User.php` | Add `getUnitsForUser()` method (queries existing `user2unit` table) |
| `include/routes.php` | Add `/activate/([A-Za-z0-9]{4,16})` and `/activate` routes; update GET `/login` to capture `?redirect=` |
| `view/page/activate.php` | **CREATE NEW** |
| `tpl/page.activate.html` | **CREATE NEW** |
| `view/page/login.php` | Add redirect-after-login support (read `$_SESSION['login_redirect']`) |
| `include/acl.policy.csv` | Add `activate` permissions |
