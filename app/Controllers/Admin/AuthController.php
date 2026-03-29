<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

class AuthController extends Controller
{
    /**
     * GET /admin/login
     */
    public function loginForm(Request $request, Response $response): Response
    {
        if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === User::ROLE_ADMIN) {
            return $this->redirect($response, '/admin');
        }

        // Consume the flash error set by AdminMiddleware
        $flashError = $_SESSION['admin_flash_error'] ?? null;
        unset($_SESSION['admin_flash_error']);

        return $this->render($response, 'admin/login', [
            'title' => 'Admin Login',
            'flash_error' => $flashError,
        ]);
    }

    /**
     * POST /admin/login
     */
    public function login(Request $request, Response $response): Response
    {
        $body     = (array) $request->getParsedBody();
        $email    = trim($body['email']    ?? '');
        $password =       $body['password'] ?? '';
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
                $errors['general'] = 'Invalid credentials.';
            } elseif ($user->role !== User::ROLE_ADMIN) {
                $errors['general'] = 'This account does not have admin access.';
            } elseif ($user->status !== User::STATUS_ACTIVE) {
                $errors['general'] = 'This account is inactive.';
            }
        }

        if (!empty($errors)) {
            return $this->render($response, 'admin/login', [
                'title'  => 'Admin Login',
                'errors' => $errors,
                'old'    => ['email' => $email],
            ]);
        }

        /** @var User $user */
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];

        return $this->redirect($response, '/admin');
    }
}
