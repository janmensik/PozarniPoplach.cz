# PozarniPoplach.cz — Admin Dashboard

Back-office administration portal and dashboard for **PozarniPoplach.cz** (fire department alarm monitoring, station units, emergency vehicles, dispatch events, authorized devices/kiosks, and advertising management).

- **Repository:** `https://github.com/janmensik/PozarniPoplach.cz`
- **Application URL:** `https://admin.pozarnipoplach.cz` (Local: `http://localhost`)

---

## Key Modules & Features

- **Dashboard:** Overview of dispatches, units, active devices, and ad delivery.
- **Dispatches (`/dispatches`):** Historical and active emergency dispatches, event classifications, addresses, responding units, and vehicles.
- **Fire Units (`/units`):** Fire brigade units/stations management, registrations, GPS coordinates, categories, and contact info.
- **Vehicles & Vehicle Types (`/vehicles`, `/vehicle-types`):** Fleet management, vehicle assignments to units, type codes, and status.
- **Authorized Devices (`/devices`):** OAuth-like device pairing/activation flow for station display kiosks and remote alarm devices.
- **Adverts & Advertisers (`/ads`, `/advertisers`):** In-kiosk advertising campaigns, SVG QR code generation, click & display tracking, and reports.
- **Event Types (`/event-types`):** Fire and rescue event classifications, categories, and icon assignments.
- **Import Logs (`/import-log`):** Ingestion logs, dispatch parsing status, and daily statistics.
- **User Management & Settings (`/users`, `/settings`):** User accounts, roles, preferences, and page schemas.

---

## Technology Stack

- **UI Framework:** AdminLTE version 3.2
- **CSS Framework:** Bootstrap 4.6
- **Frontend Interactivity:** Alpine.js 3.x
- **Backend:** PHP 8.2 (`~8.2.0`, production platform target `8.2.33`)
- **Database:** MySQL / MariaDB (direct SQL via `Janmensik\Jmlib\Database`)
- **Template Engine:** Smarty 5 (`tpl/*.html`) with custom plugins in `lib/smarty-plugins/`
- **Routing:** `bramus/router` (`include/routes.php`)
- **Access Control:** Casbin (`casbin/casbin` v4) with model & policy in `include/`
- **Testing:** Pest 3 (`pestphp/pest`)

### Composer Packages

| Package | Version | Purpose |
|---|---|---|
| `smarty/smarty` | `^5.4` | HTML template engine (`tpl/*.html`) |
| `bramus/router` | `^1.6` | Lightweight URL router |
| `casbin/casbin` | `^4.5` | Casbin RBAC access control (`include/acl.model.conf`, `include/acl.policy.csv`) |
| `chillerlan/php-qrcode` | `^6.0` | SVG QR code generation for advertisements |
| `nette/utils` | `^4.0` | Type-safe strings, arrays, and utility functions |
| `vlucas/phpdotenv` | `^5.6` | `.env` configuration loader |
| `janmensik/jmlib` | `^2.0` | Base library: `Database`, `AppData`, `Modul` base classes |
| `machinateur/roman-numerals` | `^1.2` | Roman numerals converter |
| `pestphp/pest` *(dev)* | `^3.0` | Testing framework |

---

## Technical Architecture

- **Backend Structure:** Modular MVC-like architecture.
- **Domain Models (`include/class.*.php`):** Business logic classes extending `\Janmensik\Jmlib\Modul` (`Ad`, `Advertiser`, `Device`, `DeviceAuth`, `Dispatch`, `EventType`, `ImportLog`, `LoginHistory`, `Unit`, `User`, `Vehicle`, `VehicleType`, `Version`).
- **Shared Codebase:** Shared domain classes in `include/` are maintained in exact sync with the sibling project `alarm.pozarnipoplach.cz`.
- **Database Access:** Direct SQL queries via `Janmensik\Jmlib\Database` (accessible through `$this->DB` in models).
- **Authentication & RBAC:** Session-based user authentication integrated with **Casbin** enforcer (`$CASBIN->enforce($user_role, $page, $action)`).
- **Helper Functions (`lib/functions/`):** Pure utility functions autoloaded for string, array, and date transformations.

---

## Frontend & UI Guidelines

- **Strict AdminLTE v3 Rule:** AdminLTE v3 still includes jQuery for base layout widgets, but **all custom dynamic effects, reactive state, and user interactions must use Alpine.js** (`x-data`, `x-bind`, `x-on`, `x-model`, etc.).
- **No new jQuery code:** Do not introduce new jQuery plugins or custom jQuery scripts.
- **Asynchronous Requests:** Handle modals, AJAX form submissions, and dynamic dropdowns using Alpine.js (`fetch` / `$dispatch`).
- **PHP Compatibility:** Keep PHP code strictly compliant with PHP 8.2+ syntax and modern practices.

---

## Configuration & Environment

The application uses `vlucas/phpdotenv` to load environment variables from the project root:

1. Configure `.env` with your database and application credentials:
   - `SQL_HOST`, `SQL_DATABASE`, `SQL_USER`, `SQL_PASSWORD`
   - `ABSOLUTE_URL`
   - `CASBIN_MODEL`, `CASBIN_POLICY`
2. **Local Development (`.env.localhost`):** Auto-loaded whenever `$_SERVER['SERVER_NAME']` contains `localhost`, automatically overriding `.env` values.

---

## Testing

Run the test suite using [Pest](https://pestphp.com/):

```bash
php vendor/bin/pest
```

All models, page controllers, helper functions, and authorization flows are covered by unit tests in `tests/Unit/`.

---

## License

This repository is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International license (CC BY-NC-SA 4.0).
- SPDX identifier: `CC-BY-NC-SA-4.0`.
