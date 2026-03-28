<?php

/**
 * AuthMiddleware — protect routes that require an authenticated session.
 *
 * Attach to any route or group:
 *
 *   $app->get('/dashboard', ...)->add(AuthMiddleware::class);
 *
 *   $app->group('/admin', function ($group) { ... })
 *       ->add(AuthMiddleware::class);
 *
 * Unauthenticated requests are redirected to /login.
 * The originally-requested URL is stored in the session so you can
 * redirect back after successful login (see AuthController::login).
 */

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (empty($_SESSION['user'])) {
            // Remember where the user was trying to go
            $_SESSION['redirect_after_login'] = (string) $request->getUri();

            return (new SlimResponse())
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
