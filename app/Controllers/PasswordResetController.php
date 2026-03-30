<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Mail\Mailer;
use App\Models\PasswordReset;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Slim\Views\Twig;

class PasswordResetController extends Controller
{
    public function __construct(Twig $twig, private Mailer $mailer)
    {
        parent::__construct($twig);
    }

    /**
     * GET /forgot-password
     */
    public function forgotForm(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/forgot-password', ['title' => 'Forgot Password']);
    }

    /**
     * POST /forgot-password
     * Sends reset link if email exists. Always shows the same confirmation
     * message to avoid leaking whether an email is registered.
     */
    public function forgot(Request $request, Response $response): Response
    {
        $body  = (array) $request->getParsedBody();
        $email = trim((string) ($body['email'] ?? ''));

        if (!v::email()->validate($email)) {
            return $this->render($response, 'auth/forgot-password', [
                'title'  => 'Forgot Password',
                'errors' => ['email' => 'Please enter a valid email address.'],
                'old'    => compact('email'),
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // Rate limit: one reset request per 5 minutes
            $recent = PasswordReset::where('email', $email)
                ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 300))
                ->exists();

            if (!$recent) {
                // Delete old tokens for this email
                PasswordReset::where('email', $email)->delete();

                $token = bin2hex(random_bytes(32));

                PasswordReset::create([
                    'email'      => $email,
                    'token'      => $token,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $resetUrl = (rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/')) . '/reset-password/' . $token;

                try {
                    $this->mailer->send(
                        $user->email,
                        $user->name,
                        'Reset your password',
                        'emails/reset-password',
                        [
                            'user'      => $user,
                            'reset_url' => $resetUrl,
                            'app_name'  => $_ENV['APP_NAME'] ?? 'Slim Starter',
                        ]
                    );
                } catch (\Throwable) {
                    // Silently fail — don't leak whether email was sent
                }
            }
        }

        // Always show the same message
        $_SESSION['flash_success'] = 'If that email is registered, a reset link has been sent.';
        return $this->redirect($response, '/forgot-password');
    }

    /**
     * GET /reset-password/{token}
     */
    public function resetForm(Request $request, Response $response, array $args): Response
    {
        $token  = $args['token'] ?? '';
        $record = $this->findValidToken($token);

        if (!$record) {
            $_SESSION['flash_error'] = 'This password reset link is invalid or has expired.';
            return $this->redirect($response, '/forgot-password');
        }

        return $this->render($response, 'auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    /**
     * POST /reset-password/{token}
     */
    public function reset(Request $request, Response $response, array $args): Response
    {
        $token  = $args['token'] ?? '';
        $record = $this->findValidToken($token);

        if (!$record) {
            $_SESSION['flash_error'] = 'This password reset link is invalid or has expired.';
            return $this->redirect($response, '/forgot-password');
        }

        $body            = (array) $request->getParsedBody();
        $password        = (string) ($body['password'] ?? '');
        $passwordConfirm = (string) ($body['password_confirm'] ?? '');

        $errors = [];

        if (!v::stringType()->length(8, null)->validate($password)) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->render($response, 'auth/reset-password', [
                'title'  => 'Reset Password',
                'token'  => $token,
                'errors' => $errors,
            ]);
        }

        $user = User::where('email', $record->email)->first();

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            return $this->redirect($response, '/login');
        }

        $user->password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $user->save();

        PasswordReset::where('email', $record->email)->delete();

        $_SESSION['flash_success'] = 'Your password has been reset. You can now sign in.';
        return $this->redirect($response, '/login');
    }

    /**
     * Find a password reset token that is less than 60 minutes old.
     */
    private function findValidToken(string $token): ?PasswordReset
    {
        if (empty($token)) {
            return null;
        }

        return PasswordReset::where('token', $token)
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 3600))
            ->first();
    }
}
