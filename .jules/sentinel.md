
## 2024-05-18 - [Critical] Unescaped HTTP Headers in SQL Queries (SQL Injection)
**Vulnerability:** A critical SQL Injection vulnerability was discovered in the user login tracking functionality (`updateLastLogin` method). The `getip()` function extracted the client's IP address from potentially attacker-controlled HTTP headers like `X-Forwarded-For` or `Client-IP`. This user-supplied IP string was then directly concatenated into an `INSERT INTO user_login` SQL query without escaping.
**Learning:** HTTP headers are untrusted user input just like form fields or URL parameters. An attacker could spoof the `X-Forwarded-For` header to inject arbitrary SQL statements. `INET_ATON()` in MySQL does not sanitize its arguments; passing unsanitized input to it inside a query is a direct SQLi vector.
**Prevention:**
1. Always escape or parameterize any data derived from HTTP headers before including it in a database query.
2. When parsing IP addresses from HTTP headers, rigorously validate them (e.g., using `filter_var($ip, FILTER_VALIDATE_IP)`) to ensure they are strictly formatted IP addresses before use.
