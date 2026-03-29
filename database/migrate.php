<?php

/**
 * Database migration runner.
 *
 * - Reads credentials from .env (via vlucas/phpdotenv)
 * - Creates the database if it does not exist
 * - Runs every .sql file in database/migrations/ in filename order
 * - Safe to run multiple times — all statements use IF NOT EXISTS
 *
 * Usage:
 *   Docker:          docker compose exec app php database/migrate.php
 *   cPanel / SSH:    php database/migrate.php
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

use Dotenv\Dotenv;

// ── Load .env ─────────────────────────────────────────────────────────────
$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

$host     = $_ENV['DB_HOST']      ?? '127.0.0.1';
$port     = $_ENV['DB_PORT']      ?? '3306';
$database = $_ENV['DB_DATABASE']  ?? 'slim_starter';
$username = $_ENV['DB_USERNAME']  ?? 'root';
$password = $_ENV['DB_PASSWORD']  ?? '';
$charset  = $_ENV['DB_CHARSET']   ?? 'utf8mb4';

// ── Helper: print with timestamp ──────────────────────────────────────────
$log = function (string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
};

// ── Step 1: Connect without a database to create it if missing ────────────
$log("Connecting to MySQL at {$host}:{$port} …");

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset={$charset}",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "ERROR: Could not connect to MySQL — " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$log("Connected.");

// ── Step 2: Create database if it does not exist ──────────────────────────
$log("Ensuring database `{$database}` exists …");

$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$database}`
     CHARACTER SET {$charset} COLLATE utf8mb4_unicode_ci"
);

$log("Database ready.");

// ── Step 3: Switch to the target database ─────────────────────────────────
$pdo->exec("USE `{$database}`");

// ── Step 4: Find migration files ──────────────────────────────────────────
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');

if (empty($files)) {
    $log("No migration files found in database/migrations/. Nothing to do.");
    exit(0);
}

sort($files); // Run in filename order (001, 002, …)

// ── Step 5: Run each file ─────────────────────────────────────────────────
$log("Running " . count($files) . " migration file(s) …");
$log(str_repeat('─', 50));

foreach ($files as $file) {
    $filename = basename($file);
    $log("  → {$filename}");

    $sql = file_get_contents($file);

    if ($sql === false) {
        echo "ERROR: Could not read {$filename}" . PHP_EOL;
        exit(1);
    }

    // Strip comment-only lines, then split into individual statements
    $lines = explode("\n", $sql);
    $lines = array_filter($lines, fn ($l) => !str_starts_with(trim($l), '--'));
    $statements = array_filter(
        array_map('trim', explode(';', implode("\n", $lines)))
    );

    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            echo "ERROR in {$filename}: " . $e->getMessage() . PHP_EOL;
            echo "Statement: " . substr($stmt, 0, 200) . PHP_EOL;
            exit(1);
        }
    }

    $log("     done.");
}

$log(str_repeat('─', 50));
$log("All migrations completed successfully.");
$log("Next step: php database/seeds/seed_admin.php");
