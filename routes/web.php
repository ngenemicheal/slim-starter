<?php

/**
 * Web routes — HTML responses, session-based auth.
 *
 * How to add a route:
 *   $app->get('/path', [MyController::class, 'method']);
 *
 * How to protect a group:
 *   $app->group('', function ($group) { ... })->add(AuthMiddleware::class);
 *
 * Admin routes live under /admin and are protected by AdminMiddleware.
 * Admin login is public (unprotected) so unauthenticated users can reach it.
 */

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PasswordResetController;
use App\Controllers\VerificationController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\VerifiedMiddleware;
use Slim\Routing\RouteCollectorProxy;

/** @var \Slim\App $app */

// ── Public routes ──────────────────────────────────────────────────────────
$app->get('/', [HomeController::class, 'index']);

$app->get('/login',     [AuthController::class, 'loginForm']);
$app->post('/login',    [AuthController::class, 'login']);
$app->get('/register',  [AuthController::class, 'registerForm']);
$app->post('/register', [AuthController::class, 'register']);
$app->get('/logout',    [AuthController::class, 'logout']);

// ── Email verification ──────────────────────────────────────────────────────
$app->get('/verify-notice',       [VerificationController::class, 'notice']);
$app->post('/verify/resend',      [VerificationController::class, 'resend']);
$app->get('/verify/{token:[a-f0-9]{64}}', [VerificationController::class, 'verify']);

// ── Forgot / reset password ────────────────────────────────────────────────
$app->get('/forgot-password',               [PasswordResetController::class, 'forgotForm']);
$app->post('/forgot-password',              [PasswordResetController::class, 'forgot']);
$app->get('/reset-password/{token:[a-f0-9]{64}}',  [PasswordResetController::class, 'resetForm']);
$app->post('/reset-password/{token:[a-f0-9]{64}}', [PasswordResetController::class, 'reset']);

// ── Authenticated + verified user routes ───────────────────────────────────
// Middleware runs LIFO: AuthMiddleware is outermost (runs first),
// VerifiedMiddleware is innermost (runs after auth is confirmed).
$app->group('', function (RouteCollectorProxy $group) {

    $group->get('/dashboard', [HomeController::class, 'dashboard']);

    // Add more protected web routes here:
    // $group->get('/profile', [ProfileController::class, 'show']);

})->add(VerifiedMiddleware::class)->add(AuthMiddleware::class);

// ── Admin: public login (must be outside the protected group) ──────────────
$app->get('/admin/login',  [AdminAuthController::class, 'loginForm']);
$app->post('/admin/login', [AdminAuthController::class, 'login']);

// ── Admin: protected panel ─────────────────────────────────────────────────
$app->group('/admin', function (RouteCollectorProxy $group) {

    // Dashboard
    $group->get('',  [AdminDashboardController::class, 'index']); // GET /admin

    // User CRUD
    $group->get('/users',              [AdminUserController::class, 'index']);
    $group->get('/users/{id:[0-9]+}',       [AdminUserController::class, 'show']);
    $group->get('/users/{id:[0-9]+}/edit',  [AdminUserController::class, 'edit']);
    $group->post('/users/{id:[0-9]+}/edit', [AdminUserController::class, 'update']);
    $group->post('/users/{id:[0-9]+}/delete', [AdminUserController::class, 'destroy']);

    // Add more admin routes here:
    // $group->get('/settings', [SettingsController::class, 'show']);

})->add(AdminMiddleware::class);
