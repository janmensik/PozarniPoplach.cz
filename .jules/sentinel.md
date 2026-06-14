## 2026-06-14 - SQL Injection via HTTP Headers
**Vulnerability:** SQL injection vulnerability found in `include/class.User.php` where user-controlled HTTP headers (`HTTP_CLIENT_IP` or `HTTP_X_FORWARDED_FOR`) were passed via `getip()` directly into an SQL `INSERT` statement (`INET_ATON`).
**Learning:** `getip()` relies on headers that can be spoofed by attackers. Never trust these HTTP headers directly in SQL queries without escaping.
**Prevention:** Always escape IP addresses or validate them (e.g., using `filter_var` with `FILTER_VALIDATE_IP`) before substituting them into SQL queries.
