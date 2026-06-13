## 2024-05-18 - [Fix IP Spoofing and SQL Injection Vulnerability in getip()]
**Vulnerability:** The `getip()` function parsed untrusted `HTTP_X_FORWARDED_FOR` headers directly without validation, leading to potential IP spoofing and SQL injection when stored into the database in `include/class.User.php`.
**Learning:** The custom `getip()` function lacked any validation of the source string, leading to multiple downstream vulnerabilities when the output was concatenated with SQL statements. Always validate `$_SERVER` variables controlled by user input.
**Prevention:** Always validate extracted IP addresses using `filter_var($ip, FILTER_VALIDATE_IP)`. When taking IPs from comma-separated `X-Forwarded-For` lists, explicitly parse out the first element safely before validation.
