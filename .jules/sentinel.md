## 2026-06-16 - [CRITICAL] Fix IP Spoofing Vulnerability
**Vulnerability:** The `getip()` function in `lib/functions/function.getip.php` trusted `HTTP_CLIENT_IP` and `HTTP_X_FORWARDED_FOR` headers over `REMOTE_ADDR`.
**Learning:** This is a classic IP spoofing vulnerability. An attacker could forge these headers to bypass IP-based access controls, rate limiting, and pollute audit logs with fake IP addresses. The `JmLib::getip()` function already handled this correctly.
**Prevention:** Never blindly trust HTTP headers for security-critical operations like IP tracking unless validating against a strict whitelist of trusted proxies. Always rely on the underlying network connection's `REMOTE_ADDR` as the baseline.
