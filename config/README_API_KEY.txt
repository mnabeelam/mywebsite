
Server environment variables
============================

OpenAI
------
OPENAI_API_KEY=YOUR_KEY
OPENAI_MODEL=gpt-4o-mini

Apache example (.htaccess or vhost):
SetEnv OPENAI_API_KEY YOUR_KEY
SetEnv OPENAI_MODEL gpt-4o-mini

Local development:
Add OPENAI_API_KEY to config/local.php (see config/local.example.php)


Admin login (Phase 2)
---------------------
ADMIN_USERNAME=your_admin_username
ADMIN_PASSWORD=your_strong_password

Apache example:
SetEnv ADMIN_USERNAME your_admin_username
SetEnv ADMIN_PASSWORD your_strong_password

Optional safer option:
SetEnv ADMIN_PASSWORD_HASH your_bcrypt_hash

Generate hash:
php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"

Never put these values in JavaScript files or commit them to git.

Admin pages:
/admin/index.html
/admin/dashboard.html
