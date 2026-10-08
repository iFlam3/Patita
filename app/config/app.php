<?php

declare(strict_types=1);

use App\Core\Env;

/*
 * Configuración general. Todos los valores sensibles o dependientes del
 * despliegue provienen de variables de entorno (.env).
 */
return [
    'name'        => Env::get('APP_NAME', 'Patita'),
    'env'         => Env::get('APP_ENV', 'production'),
    'debug'       => Env::bool('APP_DEBUG', false),
    'url'         => rtrim((string) Env::get('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'demo_notice' => Env::bool('APP_DEMO_NOTICE', false),
    'contact_email' => (string) Env::get('CONTACT_EMAIL', (string) Env::get('MAIL_FROM_ADDRESS', 'hola@example.test')),
    'contact_phone' => (string) Env::get('CONTACT_PHONE', ''),

    'country'  => strtoupper((string) Env::get('APP_COUNTRY', 'VE')),
    'locale'   => Env::get('APP_LOCALE', 'es-US'),
    'currency' => strtoupper((string) Env::get('APP_CURRENCY', 'USD')),
    'timezone' => Env::get('APP_TIMEZONE', 'America/Caracas'),

    'db' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => Env::int('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'patita'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'session' => [
        'name'          => Env::get('SESSION_NAME', 'patita_session'),
        'secure'        => strtolower((string) Env::get('SESSION_SECURE_COOKIE', 'auto')),
        'idle_minutes'  => Env::int('SESSION_IDLE_MINUTES', 120),
    ],

    'shop' => [
        'shipping_flat_rate'  => (string) Env::get('SHIPPING_FLAT_RATE', '5.99'),
        'shipping_free_from'  => (string) Env::get('SHIPPING_FREE_FROM', '0'),
        'shipping_countries'  => array_map('strtoupper', Env::list('SHIPPING_COUNTRIES', (string) Env::get('APP_COUNTRY', 'VE'))),
        'payment_methods'     => Env::list('PAYMENT_METHODS', 'bank_transfer,cash_on_delivery'),
        'bank_details'        => (string) Env::get('BANK_TRANSFER_DETAILS', ''),
        'max_qty_per_item'    => max(1, Env::int('MAX_QTY_PER_ITEM', 20)),
        'per_page'            => 12,
    ],

    'mail' => [
        'transport'  => strtolower((string) Env::get('MAIL_TRANSPORT', 'log')),
        'from'       => (string) Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.test'),
        'from_name'  => (string) Env::get('MAIL_FROM_NAME', 'Patita'),
        'host'       => (string) Env::get('SMTP_HOST', ''),
        'port'       => Env::int('SMTP_PORT', 587),
        'username'   => (string) Env::get('SMTP_USERNAME', ''),
        'password'   => (string) Env::get('SMTP_PASSWORD', ''),
        'encryption' => strtolower((string) Env::get('SMTP_ENCRYPTION', 'tls')),
    ],

    'auth' => [
        'login_max_attempts'        => max(1, Env::int('LOGIN_MAX_ATTEMPTS', 5)),
        'login_max_attempts_per_ip' => max(1, Env::int('LOGIN_MAX_ATTEMPTS_PER_IP', 20)),
        'login_decay_minutes'       => max(1, Env::int('LOGIN_DECAY_MINUTES', 15)),
        'reset_ttl_minutes'         => max(5, Env::int('PASSWORD_RESET_TTL_MINUTES', 60)),
        'reset_max_requests'        => max(1, Env::int('PASSWORD_RESET_MAX_REQUESTS', 3)),
        'password_min_length'       => 8,
    ],

    'import' => [
        'allowed_sources'    => Env::list('IMPORT_ALLOWED_SOURCES', ''),
        'user_agent'         => (string) Env::get('IMPORT_USER_AGENT', 'PatitaCatalogBot/1.0 (+https://example.test/bot)'),
        'delay_seconds'      => max(1, Env::int('IMPORT_DELAY_SECONDS', 3)),
        'timeout_seconds'    => max(5, Env::int('IMPORT_TIMEOUT_SECONDS', 15)),
        'max_retries'        => min(5, max(0, Env::int('IMPORT_MAX_RETRIES', 2))),
        'cache_ttl_hours'    => max(0, Env::int('IMPORT_CACHE_TTL_HOURS', 24)),
        'copy_descriptions'  => Env::bool('IMPORT_COPY_DESCRIPTIONS', false),
        'use_remote_images'  => Env::bool('IMPORT_USE_REMOTE_IMAGES', false),
    ],
];
