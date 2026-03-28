<?php

/**
 * Web routes — HTML responses, session-based auth.
 *
 * How to add a new route:
 *   $app->get('/path', [MyController::class, 'method']);
 *   $app->post('/path', [MyController::class, 'method']);
 *
 * How to protect a group of routes:
 *   $app->group('/admin', function ($group) {
 *       $group->get('/dashboard', [AdminController::class, 'index']);
 *   })->add(AuthMiddleware::class);
 */

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;

/** @var \Slim\App $app */

// ── Public routes ──────────────────────────────────────────────────────────
$app->get('/', [HomeController::class, 'index']);

$app->get('/login',     [AuthController::class, 'loginForm']);
$app->post('/login',    [AuthController::class, 'login']);
$app->get('/register',  [AuthController::class, 'registerForm']);
$app->post('/register', [AuthController::class, 'register']);
$app->get('/logout',    [AuthController::class, 'logout']);

// ── Protected routes (login required) ─────────────────────────────────────
$app->group('', function ($group) {

    $group->get('/dashboard', [HomeController::class, 'dashboard']);

    // Add more protected web routes here:
    // $group->get('/profile', [ProfileController::class, 'show']);
    // $group->post('/profile', [ProfileController::class, 'update']);

})->add(AuthMiddleware::class);
