<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class VerifiedMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory) {}

    public function process(Request $request, Handler $handler): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (empty($user['email_verified_at'])) {
            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', '/verify-notice')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
