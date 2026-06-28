<?php
/**
 * Local settings — do NOT commit this file to git.
 */
return [
    'ADMIN_USERNAME' => 'your_username_here',
    'ADMIN_PASSWORD_HASH' => '',
    'ADMIN_PASSWORD' => '',
    'OPENAI_API_KEY' => '',
    'OPENAI_MODEL' => 'gpt-4o-mini',
    // Set true to enable the assistant. Works fully without OPENAI_API_KEY (knowledge + shop search).
    // Add OPENAI_API_KEY later only if you want GPT-generated answers.
    'AI_ENABLED' => true,
    'CONTACT_EMAIL' => 'your_email@example.com',
    // Optional extra inbox for admin alerts (orders, contact form). Also uses notification email + DB user emails.
    'ADMIN_NOTIFY_EMAIL' => '',
    // Optional SMTP (future): set SMTP_HOST to enable outbound mail instead of PHP mail().
    'SMTP_HOST' => '',
    'SMTP_PORT' => '587',
    'SMTP_USER' => '',
    'SMTP_PASSWORD' => '',
    'SMTP_FROM' => '',
    // Optional fallback admin IP whitelist (comma-separated). Dashboard can override this.
    'ADMIN_IP_WHITELIST' => '',

    // Database — SQLite is used by default (auto-created under php/storage/database/).
    'DB_DRIVER' => 'sqlite',
    'DB_PATH' => '',
    // MySQL (set DB_DRIVER => 'mysql' and fill in below):
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'portfolio',
    'DB_USER' => '',
    'DB_PASSWORD' => '',
];
