<?php

/**
 * Eloquent / illuminate/database connection config.
 * Values are pulled from .env via phpdotenv.
 */

declare(strict_types=1);

return [
    'driver'    => $_ENV['DB_DRIVER']    ?? 'mysql',
    'host'      => $_ENV['DB_HOST']      ?? '127.0.0.1',
    'port'      => $_ENV['DB_PORT']      ?? '3306',
    'database'  => $_ENV['DB_DATABASE']  ?? 'slim_starter',
    'username'  => $_ENV['DB_USERNAME']  ?? 'root',
    'password'  => $_ENV['DB_PASSWORD']  ?? '',
    'charset'   => $_ENV['DB_CHARSET']   ?? 'utf8mb4',
    'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
    'prefix'    => '',
];
