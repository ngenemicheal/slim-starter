<?php

/**
 * Base controller.
 *
 * Provides helpers shared by all controllers:
 *   - render()  — render a PHP view template
 *   - json()    — write a JSON response
 *   - redirect() — shortcut for 302 redirects
 */

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

abstract class Controller
{
    /**
     * Render a PHP template from the views/ directory.
     *
     * Variables in $data are extract()ed into the template scope.
     *
     * Usage: return $this->render($response, 'auth/login', ['title' => 'Login']);
     */
    protected function render(Response $response, string $view, array $data = []): Response
    {
        // Make all $data keys available as variables inside the template
        extract($data, EXTR_SKIP);

        ob_start();
        require APP_ROOT . '/views/' . $view . '.php';
        $html = (string) ob_get_clean();

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
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
}
