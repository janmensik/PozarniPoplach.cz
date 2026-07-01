
## 2024-05-18 - [Critical] Unescaped HTTP Headers in SQL Queries (SQL Injection)
**Vulnerability:** A critical SQL Injection vulnerability was discovered in the user login tracking functionality (`updateLastLogin` method). The `getip()` function extracted the client's IP address from potentially attacker-controlled HTTP headers like `X-Forwarded-For` or `Client-IP`. This user-supplied IP string was then directly concatenated into an `INSERT INTO user_login` SQL query without escaping.
**Learning:** HTTP headers are untrusted user input just like form fields or URL parameters. An attacker could spoof the `X-Forwarded-For` header to inject arbitrary SQL statements. `INET_ATON()` in MySQL does not sanitize its arguments; passing unsanitized input to it inside a query is a direct SQLi vector.
**Prevention:**
1. Always escape or parameterize any data derived from HTTP headers before including it in a database query.
2. When parsing IP addresses from HTTP headers, rigorously validate them (e.g., using `filter_var($ip, FILTER_VALIDATE_IP)`) to ensure they are strictly formatted IP addresses before use.

## 2024-06-26 - Predictable Permanent Login Cookie Hash
**Vulnerability:** The "Remember Me" functionality generated a persistent login hash using only `sha1(id . email)`. This allowed an attacker to easily forge a valid login cookie for any known user, as user IDs and emails are often public or easily guessable. This is a critical auth bypass risk.
**Learning:** Never generate authentication tokens or hashes based solely on predictable, non-secret user data. Any hash used for authentication must incorporate a secret piece of data known only to the user or the server.
**Prevention:** Include the user's password hash (`u.password`) in the `CONCAT` statement when generating and verifying the permanent login hash: `SHA1(CONCAT(u.id, u.email, u.password))`. This not only secures the hash but also ensures that all permanent sessions are invalidated automatically if the user changes their password.

## 2024-06-26 - Insecure Randomness for Passwords
**Vulnerability:** The `generatePassword()` function utilized `rand()` for character selection. `rand()` is not cryptographically secure, meaning generated passwords could potentially be predicted if the PRNG state is known or guessed.
**Learning:** Functions generating secrets (passwords, tokens, keys) must use secure randomness sources. Standard `rand()` or `mt_rand()` are insufficient for security-sensitive operations.
**Prevention:** Always use `random_int()` in PHP for secure random integer generation within a range, especially when generating passwords or cryptographic material.
