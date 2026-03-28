<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    /**
     * GET /  — public landing page.
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'home', [
            'title' => 'Welcome',
            'user'  => $_SESSION['user'] ?? null,
        ]);
    }

    /**
     * GET /dashboard  — protected, requires auth (via AuthMiddleware).
     */
    public function dashboard(Request $request, Response $response): Response
    {
        return $this->render($response, 'dashboard', [
            'title' => 'Dashboard',
            'user'  => $_SESSION['user'],
        ]);
    }
}
