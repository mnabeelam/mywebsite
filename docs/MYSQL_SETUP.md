# MySQL setup (Windows / local)

The site supports **SQLite** (default) or **MySQL**. Admin passwords are always stored as **bcrypt hashes** in the `users` table — not plain text.

## Option A — Keep SQLite (easiest)

No extra setup. On first run the site creates `php/storage/database/site.sqlite` and seeds your admin user from `config/local.php` using a hashed password.

After login works, remove `ADMIN_PASSWORD` from `config/local.php` and manage passwords from **Admin → Account** or **Users & Roles**.

## Option B — Use MySQL

### 1. Install MySQL

Install MySQL Server 8.x and note the root password.

### 2. Configure `config/local.php`

```php
'DB_DRIVER' => 'mysql',
'DB_HOST' => '127.0.0.1',
'DB_PORT' => '3306',
'DB_NAME' => 'portfolio',
'DB_USER' => 'portfolio_user',
'DB_PASSWORD' => 'your_strong_password',
'DB_SETUP_USER' => 'root',
'DB_SETUP_PASSWORD' => 'your_mysql_root_password',
```

Keep `ADMIN_USERNAME` for the first admin account name. Plain `ADMIN_PASSWORD` is only used once to seed the hashed row in MySQL.

### 3. Create database and tables

```bat
php scripts\setup-mysql.php
```

Or manually:

```bat
mysql -u root -p < scripts\setup-mysql-database.sql
```

Then load the site once (Apache) so migrations create all tables.

### 4. Log in and secure

1. Open admin and sign in with your username/password.
2. Change password in **Account** (saved as hash in MySQL).
3. Remove `ADMIN_PASSWORD` from `config/local.php`.

## Backups (Admin → Backup & Data)

| Type | Includes |
|------|----------|
| **Site files only** | Shop files, certs, knowledge, uploads, JSON settings |
| **Database only** | SQL export of all tables (users with hashed passwords) |
| **Site + database** | Recommended full backup |

All backups are password-protected ZIP files.

## PHP extensions

Enable in `php.ini`:

- `extension=pdo_mysql` (MySQL)
- `extension=pdo_sqlite` (SQLite)
- `extension=zip` (backups)

Restart Apache after changes.
