# Fix: Browser Shows PHP Code Instead of Login Page

If you see `<?php` code in the browser, **PHP was not running**.
Your PC uses **Apache 2.4** at `C:\Apache24` with site `mywebsite.local`.

**This has now been fixed:** PHP 8.3 was installed and connected to Apache.

---

## Test now (use these URLs)

| Page | URL |
|------|-----|
| PHP test | http://127.0.0.1/php/test.php |
| Admin login | http://127.0.0.1/admin/index.php |
| Homepage | http://127.0.0.1/index.html |

**Good result for PHP test:** plain text saying `PHP is working.`  
**Bad result:** still shows `<?php` code → run `scripts/check-php-apache.bat`

---

## Quick check script

Double-click: `scripts/check-php-apache.bat`

---

## Your Apache setup (for reference)

| Item | Value |
|------|-------|
| Apache | `C:\Apache24` |
| Site folder | `E:\Nabeel Data\mywebsite\ver2\mywebsite` |
| Domain | `mywebsite.local` |
| PHP config | `C:\Apache24\conf\extra\httpd-php.conf` |

If Apache is restarted and PHP stops working, ask IT to verify `httpd-php.conf` is included.

---

## Admin login

1. Open: http://127.0.0.1/admin/index.php
2. Use the **form** (not URL parameters)
3. Credentials are in `config/local.php`

---

## Alternative: built-in PHP server (port 8080)

Only if Apache is stopped:

1. Double-click `scripts/start-local-server.bat`
2. Use http://127.0.0.1:8080/admin/index.php

Do not run Apache (port 80) and port 8080 server at the same time for the same test.

---

## Checklist

```
[ ] http://127.0.0.1/php/test.php shows "PHP is working"
[ ] http://127.0.0.1/admin/index.php shows login FORM (not code)
[ ] Login works with config/local.php credentials
```
