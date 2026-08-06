
## 2024-05-18 - [Critical] Unescaped HTTP Headers in SQL Queries (SQL Injection)
**Vulnerability:** A critical SQL Injection vulnerability was discovered in the user login tracking functionality (`updateLastLogin` method). The `getip()` function extracted the client's IP address from potentially attacker-controlled HTTP headers like `X-Forwarded-For` or `Client-IP`. This user-supplied IP string was then directly concatenated into an `INSERT INTO user_login` SQL query without escaping.
**Learning:** HTTP headers are untrusted user input just like form fields or URL parameters. An attacker could spoof the `X-Forwarded-For` header to inject arbitrary SQL statements. `INET_ATON()` in MySQL does not sanitize its arguments; passing unsanitized input to it inside a query is a direct SQLi vector.
**Prevention:**
1. Always escape or parameterize any data derived from HTTP headers before including it in a database query.
2. When parsing IP addresses from HTTP headers, rigorously validate them (e.g., using `filter_var($ip, FILTER_VALIDATE_IP)`) to ensure they are strictly formatted IP addresses before use.

## 2024-06-25 - [Critical] Unrestricted File Upload (RCE)
**Vulnerability:** A critical Unrestricted File Upload vulnerability was discovered in `view/page/ad-edit.php`. The code relied solely on the extension provided by the user in `$_FILES['banner_image']['name']` to construct the target filename. This allowed an attacker to upload an arbitrary PHP file (e.g., `shell.php`) into `upload/ads/` which resides in the webroot, leading directly to Remote Code Execution (RCE).
**Learning:** Never trust user-provided file names or extensions. If you need to store files in the webroot, they must undergo strict validation against a whitelist of safe file types, and ideally, they should be stored outside the web root or served through a script that enforces access control.
**Prevention:**
1. Always enforce a strict whitelist of safe file extensions (e.g., `['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']`).
2. Do not execute or interpret files located in upload directories.
## 2024-06-26 - Predictable Permanent Login Cookie Hash
**Vulnerability:** The "Remember Me" functionality generated a persistent login hash using only `sha1(id . email)`. This allowed an attacker to easily forge a valid login cookie for any known user, as user IDs and emails are often public or easily guessable. This is a critical auth bypass risk.
**Learning:** Never generate authentication tokens or hashes based solely on predictable, non-secret user data. Any hash used for authentication must incorporate a secret piece of data known only to the user or the server.
**Prevention:** Include the user's password hash (`u.password`) in the `CONCAT` statement when generating and verifying the permanent login hash: `SHA1(CONCAT(u.id, u.email, u.password))`. This not only secures the hash but also ensures that all permanent sessions are invalidated automatically if the user changes their password.

## 2024-06-26 - Insecure Randomness for Passwords
**Vulnerability:** The `generatePassword()` function utilized `rand()` for character selection. `rand()` is not cryptographically secure, meaning generated passwords could potentially be predicted if the PRNG state is known or guessed.
**Learning:** Functions generating secrets (passwords, tokens, keys) must use secure randomness sources. Standard `rand()` or `mt_rand()` are insufficient for security-sensitive operations.
**Prevention:** Always use `random_int()` in PHP for secure random integer generation within a range, especially when generating passwords or cryptographic material.

## 2024-06-26 - [Critical] Insecure Deserialization (CWE-502)
**Vulnerability:** A critical insecure deserialization vulnerability was found where `unserialize()` was called on untrusted data without setting `allowed_classes` to `false`. The vulnerability existed in `include/class.User.php` when deserializing user configuration from the database (`page_schema`), and in `lib/functions/function.kurzy_cnb.php` when deserializing cached currency files. If an attacker manages to tamper with this data, PHP Object Injection may lead to Remote Code Execution (RCE) via `__wakeup()`, `__destruct()`, or similar magic methods in loaded classes.
**Learning:** `unserialize()` is inherently dangerous when applied to untrusted or potentially compromised data. Even data from caches or databases must be treated cautiously, particularly in environments susceptible to other vulnerabilities (like SQL injection or local file inclusion).
**Prevention:**
1. Always set `['allowed_classes' => false]` in `unserialize()` calls if objects are not strictly required (e.g., when deserializing arrays or scalars).
2. Preferably use `json_encode()` and `json_decode()` instead of `serialize()` and `unserialize()` when exchanging data.
## 2024-06-25 - [High] Incomplete File Upload Validation
**Vulnerability:** An incomplete file upload validation in `view/page/ad-edit.php` allowed files to be uploaded based purely on their extension, leaving the system susceptible to MIME type spoofing and related upload vulnerabilities.
**Learning:** Checking only the file extension is not sufficient, as it doesn't guarantee the file content matches. Validating the file contents (MIME type) is crucial to defend against malicious uploads.
**Prevention:** Always use `finfo_file` (or similar file content analysis tools) alongside extension validation to ensure uploaded files genuinely correspond to the expected types, avoiding reliance on user-provided extensions or spoofable HTTP headers like `$_FILES['...']['type']`.

## 2024-10-25 - [Medium] Insecure Deserialization Mitigation

**Vulnerability:** Although the `unserialize()` call in `include/class.User.php` was somewhat mitigated by using `['allowed_classes' => false]`, keeping `unserialize()` presents unnecessary risks related to PHP's complex serialization format, especially if the data gets tampered with.

**Learning:** It is always a better security practice to use `json_encode()` and `json_decode()` instead of PHP's native `serialize()` and `unserialize()` when persisting simple configuration data (like the page schema). Using a backward compatibility check (`strpos($raw, 'a:') === 0 || strpos($raw, 'O:') === 0`) allows for a smooth migration of legacy data to the new format without breaking existing user configurations.

**Prevention:** Always prefer JSON for data serialization, and only use `serialize()` when absolutely necessary for complex objects (which itself should be avoided in most data persistence scenarios).
