<?php

declare(strict_types=1);

// ── Application root (one level above public/) ────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// ── Composer autoloader ───────────────────────────────────────────────────
require APP_ROOT . '/vendor/autoload.php';

// ── Bootstrap the app and run it ─────────────────────────────────────────
$app = require APP_ROOT . '/bootstrap/app.php';

$app->run();
