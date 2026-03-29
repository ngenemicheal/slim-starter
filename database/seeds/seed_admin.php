<?php

/**
 * Admin user seeder.
 *
 * Creates (or resets) the default admin account:
 *   Email:    admin@example.com
 *   Password: admin123
 *
 * IMPORTANT: Run migrations before this seeder:
 *   docker compose exec app php database/migrate.php
 *
 * Usage:
 *   Docker:        docker compose exec app php database/seeds/seed_admin.php
 *   cPanel / SSH:  php database/seeds/seed_admin.php
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__, 2));

require APP_ROOT . '/vendor/autoload.php';

use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment
$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// Boot Eloquent
$capsule = new Capsule();
$capsule->addConnection(require APP_ROOT . '/config/database.php');
$capsule->bootEloquent();

// Seed
$email    = 'admin@example.com';
$password = 'admin123';
$name     = 'Admin';

$existing = User::where('email', $email)->first();

$data = [
    'name'     => $name,
    'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
    'role'     => User::ROLE_ADMIN,
    'status'   => User::STATUS_ACTIVE,
];

if ($existing) {
    $existing->update($data);
    echo "Admin user updated.\n";
} else {
    User::create(array_merge($data, ['email' => $email]));
    echo "Admin user created.\n";
}

echo "  Email:    {$email}\n";
echo "  Password: {$password}\n";
echo "  Done — change the password after first login!\n";
