<?php

/**
 * Application bootstrap.
 *
 * Responsibilities:
 *  1. Load .env
 *  2. Configure error reporting
 *  3. Start the session
 *  4. Boot Eloquent ORM
 *  5. Build the PHP-DI container
 *  6. Create the Slim app
 *  7. Register middleware
 *  8. Load routes (based on APP_MODE)
 *  9. Return $app
 */

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Factory\AppFactory;

// ── 1. Load .env ──────────────────────────────────────────────────────────
// safeLoad() silently ignores a missing .env (handy when env vars are set
// at the server level, e.g. cPanel Environment Variables).
$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// ── 2. Error reporting ────────────────────────────────────────────────────
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

// ── 3. Session ────────────────────────────────────────────────────────────
$sessionPath = $_ENV['SESSION_PATH'] ?? (APP_ROOT . '/storage/sessions');

// Resolve the path if it's relative (e.g. "../storage/sessions")
if (!str_starts_with($sessionPath, '/')) {
    $sessionPath = APP_ROOT . '/' . ltrim($sessionPath, './');
}

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}

session_save_path(realpath($sessionPath));
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    // Only send the session cookie over HTTPS in production
    'cookie_secure'   => ($_ENV['APP_ENV'] ?? 'development') === 'production',
    'gc_maxlifetime'  => (int) ($_ENV['SESSION_LIFETIME'] ?? 120) * 60,
]);

// ── 4. Eloquent ORM ───────────────────────────────────────────────────────
$capsule = new Capsule();
$capsule->addConnection(require APP_ROOT . '/config/database.php');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// ── 5. DI container ───────────────────────────────────────────────────────
$builder = new ContainerBuilder();
$builder->addDefinitions(require APP_ROOT . '/config/app.php');

// Enable compiled container in production for a meaningful speed boost.
// Disable compilation during development so config changes are instant.
if (!$debug) {
    $builder->enableCompilation(APP_ROOT . '/storage/cache/di');
}

$container = $builder->build();

// ── 6. Create Slim app ────────────────────────────────────────────────────
AppFactory::setContainer($container);
$app = AppFactory::create();

// ── 7. Middleware stack ───────────────────────────────────────────────────
// Parse application/json, application/x-www-form-urlencoded, and multipart
$app->addBodyParsingMiddleware();

// Required for routing to work
$app->addRoutingMiddleware();

// Error handling — shows detailed traces in debug mode
$errorMiddleware = $app->addErrorMiddleware($debug, true, true);

// In API mode, always return JSON errors instead of HTML
$mode = strtolower($_ENV['APP_MODE'] ?? 'web');

if ($mode === 'api') {
    $errorMiddleware->setDefaultErrorHandler(
        function (
            \Psr\Http\Message\ServerRequestInterface $request,
            \Throwable $exception,
            bool $displayErrorDetails
        ) use ($app): \Psr\Http\Message\ResponseInterface {
            $status  = $exception->getCode() >= 400 && $exception->getCode() < 600
                ? (int) $exception->getCode()
                : 500;

            $payload = ['error' => $exception->getMessage()];
            if ($displayErrorDetails) {
                $payload['trace'] = $exception->getTraceAsString();
            }

            $response = $app->getResponseFactory()->createResponse($status);
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }
    );
}

// ── 8. Routes ─────────────────────────────────────────────────────────────
if ($mode === 'api') {
    // Pure API mode: only load API routes (no HTML views, no sessions needed
    // for routing — though they're still available if you want them)
    require APP_ROOT . '/routes/api.php';
} else {
    // Web mode: load HTML routes plus API routes grouped under /api
    require APP_ROOT . '/routes/web.php';
    require APP_ROOT . '/routes/api.php';
}

// ── 9. Return app ─────────────────────────────────────────────────────────
return $app;
