-- Portfolio site MySQL database setup
-- Run as MySQL root (adjust names/passwords before use):
--   mysql -u root -p < scripts/setup-mysql-database.sql

CREATE DATABASE IF NOT EXISTS portfolio
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'portfolio_user'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON portfolio.* TO 'portfolio_user'@'localhost';
FLUSH PRIVILEGES;
