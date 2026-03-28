<?php

/**
 * API routes — JSON responses.
 *
 * In web mode:  all routes here are prefixed with /api
 * In api mode:  routes are at their declared paths (no /api prefix added
 *               automatically — declare them as /api/... yourself, or remove
 *               the group and define flat routes)
 *
 * How to add an authenticated API endpoint:
 *   use App\Middleware\ApiAuthMiddleware;
 *
 *   $group->get('/me', [UserController::class, 'me'])
 *         ->add(ApiAuthMiddleware::class);
 */

declare(strict_types=1);

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

/** @var \Slim\App $app */

$app->group('/api', function (RouteCollectorProxy $group) {

    // ── Health check ───────────────────────────────────────────────────────
    // GET /api/health  →  { "status": "ok", "timestamp": "..." }
    $group->get('/health', function (Request $request, Response $response): Response {
        $body = json_encode([
            'status'    => 'ok',
            'timestamp' => date('c'),
        ]);
        $response->getBody()->write($body);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── List users (example — protect this in production!) ─────────────────
    // GET /api/users  →  [ { "id": 1, "name": "...", "email": "..." }, ... ]
    $group->get('/users', function (Request $request, Response $response): Response {
        $users = User::select('id', 'name', 'email', 'created_at')->get();
        $response->getBody()->write($users->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── Add more API endpoints here ────────────────────────────────────────
    // $group->get('/posts',        [PostController::class, 'index']);
    // $group->post('/posts',       [PostController::class, 'store']);
    // $group->get('/posts/{id}',   [PostController::class, 'show']);
    // $group->put('/posts/{id}',   [PostController::class, 'update']);
    // $group->delete('/posts/{id}',[PostController::class, 'destroy']);

});
