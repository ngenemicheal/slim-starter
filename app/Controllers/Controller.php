<?php

/**
 * Base controller.
 *
 * Provides helpers shared by all controllers:
 *   - render()   — render a Twig (or PHP) view template
 *   - json()     — write a JSON response
 *   - redirect() — shortcut for 302 redirects
 *
 * Template engine is chosen via APP_TEMPLATE_ENGINE in .env:
 *   "twig" (default) — looks for views/<name>.twig
 *   "php"            — looks for views/<name>.php (legacy)
 */

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class Controller
{
    /**
     * PHP-DI injects the Twig instance automatically.
     * Sub-classes that need extra dependencies should define their own
     * constructor and call parent::__construct($twig).
     */
    public function __construct(protected Twig $twig) {}

    /**
     * Render a view template.
     *
     * With Twig  (default): renders views/<name>.twig
     * With PHP   (fallback): renders views/<name>.php
     *
     * Usage: return $this->render($response, 'auth/login', ['title' => 'Login']);
     */
    protected function render(Response $response, string $view, array $data = []): Response
    {
        if (strtolower($_ENV['APP_TEMPLATE_ENGINE'] ?? 'twig') !== 'twig') {
            return $this->renderPhp($response, $view, $data);
        }

        return $this->twig->render($response, $view . '.twig', $data);
    }

    /**
     * Write a JSON payload to the response.
     *
     * Usage: return $this->json($response, ['user' => $user]);
     *        return $this->json($response, ['error' => 'Not found'], 404);
     */
    protected function json(Response $response, mixed $data, int $status = 200): Response
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($body !== false ? $body : '{}');
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Return a redirect response.
     *
     * Usage: return $this->redirect($response, '/dashboard');
     */
    protected function redirect(Response $response, string $url, int $status = 302): Response
    {
        return $response->withHeader('Location', $url)->withStatus($status);
    }

    /**
     * Legacy PHP template renderer — used when APP_TEMPLATE_ENGINE=php.
     */
    private function renderPhp(Response $response, string $view, array $data = []): Response
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require APP_ROOT . '/views/' . $view . '.php';
        $html = (string) ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
