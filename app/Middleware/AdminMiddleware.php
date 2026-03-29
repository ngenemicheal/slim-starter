<?php

/**
 * AdminMiddleware — restricts access to users with role = 'admin'.
 *
 * Attach to admin routes or groups:
 *
 *   $app->group('/admin', function ($group) { ... })
 *       ->add(AdminMiddleware::class);
 *
 * - Unauthenticated requests  → /admin/login
 * - Authenticated non-admins  → /admin/login with a flash error
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AdminMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (empty($_SESSION['user'])) {
            return (new SlimResponse())
                ->withHeader('Location', '/admin/login')
                ->withStatus(302);
        }

        if (($_SESSION['user']['role'] ?? User::ROLE_USER) !== User::ROLE_ADMIN) {
            $_SESSION['admin_flash_error'] = 'You do not have admin privileges.';
            return (new SlimResponse())
                ->withHeader('Location', '/admin/login')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
