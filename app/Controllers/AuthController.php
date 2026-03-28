<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

class AuthController extends Controller
{
    // ── Register ───────────────────────────────────────────────────────────

    /**
     * GET /register
     */
    public function registerForm(Request $request, Response $response): Response
    {
        // Already logged in — bounce to dashboard
        if (!empty($_SESSION['user'])) {
            return $this->redirect($response, '/dashboard');
        }

        return $this->render($response, 'auth/register', ['title' => 'Create Account']);
    }

    /**
     * POST /register
     */
    public function register(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $name     = trim((string) ($body['name']     ?? ''));
        $email    = trim((string) ($body['email']    ?? ''));
        $password =       (string) ($body['password'] ?? '');

        $errors = $this->validateRegistration($name, $email, $password);

        // Re-render form with errors
        if (!empty($errors)) {
            return $this->render($response, 'auth/register', [
                'title'  => 'Create Account',
                'errors' => $errors,
                'old'    => compact('name', 'email'),
            ]);
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->startUserSession($user);

        return $this->redirect($response, '/dashboard');
    }

    // ── Login ──────────────────────────────────────────────────────────────

    /**
     * GET /login
     */
    public function loginForm(Request $request, Response $response): Response
    {
        if (!empty($_SESSION['user'])) {
            return $this->redirect($response, '/dashboard');
        }

        return $this->render($response, 'auth/login', ['title' => 'Sign In']);
    }

    /**
     * POST /login
     */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $email    = trim((string) ($body['email']    ?? ''));
        $password =       (string) ($body['password'] ?? '');
        $errors   = [];

        if (!v::email()->validate($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (empty($errors)) {
            $user = User::where('email', $email)->first();

            if (!$user || !$user->verifyPassword($password)) {
                // Use a generic message to avoid leaking whether the email exists
                $errors['general'] = 'The credentials you entered are incorrect.';
            }
        }

        if (!empty($errors)) {
            return $this->render($response, 'auth/login', [
                'title'  => 'Sign In',
                'errors' => $errors,
                'old'    => ['email' => $email],
            ]);
        }

        /** @var User $user */
        $this->startUserSession($user);

        return $this->redirect($response, '/dashboard');
    }

    // ── Logout ─────────────────────────────────────────────────────────────

    /**
     * GET /logout
     */
    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        return $this->redirect($response, '/login');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function validateRegistration(string $name, string $email, string $password): array
    {
        $errors = [];

        if (!v::stringType()->length(2, 100)->validate($name)) {
            $errors['name'] = 'Name must be between 2 and 100 characters.';
        }

        if (!v::email()->validate($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (User::where('email', $email)->exists()) {
            $errors['email'] = 'That email address is already registered.';
        }

        if (!v::stringType()->length(8, null)->validate($password)) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        return $errors;
    }

    private function startUserSession(User $user): void
    {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}
