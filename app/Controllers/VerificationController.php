<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Mail\Mailer;
use App\Models\EmailVerification;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class VerificationController extends Controller
{
    public function __construct(Twig $twig, private Mailer $mailer)
    {
        parent::__construct($twig);
    }

    /**
     * GET /verify-notice
     * Page shown after registration prompting the user to check their email.
     */
    public function notice(Request $request, Response $response): Response
    {
        if (empty($_SESSION['user'])) {
            return $this->redirect($response, '/login');
        }

        if (!empty($_SESSION['user']['email_verified_at'])) {
            return $this->redirect($response, '/dashboard');
        }

        return $this->render($response, 'auth/verify-notice', [
            'title' => 'Verify Your Email',
            'email' => $_SESSION['user']['email'],
        ]);
    }

    /**
     * GET /verify/{token}
     * Handles the link clicked from the verification email.
     */
    public function verify(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'] ?? '';

        $record = EmailVerification::where('token', $token)->first();

        if (!$record) {
            $_SESSION['flash_error'] = 'This verification link is invalid or has already been used.';
            return $this->redirect($response, '/verify-notice');
        }

        $user = User::find($record->user_id);

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            return $this->redirect($response, '/login');
        }

        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        // Delete all verification tokens for this user
        EmailVerification::where('user_id', $user->id)->delete();

        // Update the session
        if (!empty($_SESSION['user']) && $_SESSION['user']['id'] === $user->id) {
            $_SESSION['user']['email_verified_at'] = (string) $user->email_verified_at;
        }

        $_SESSION['flash_success'] = 'Your email has been verified. Welcome!';
        return $this->redirect($response, '/dashboard');
    }

    /**
     * POST /verify/resend
     * Resends the verification email (rate-limited to once per 5 minutes).
     */
    public function resend(Request $request, Response $response): Response
    {
        if (empty($_SESSION['user'])) {
            return $this->redirect($response, '/login');
        }

        if (!empty($_SESSION['user']['email_verified_at'])) {
            return $this->redirect($response, '/dashboard');
        }

        $userId = $_SESSION['user']['id'];

        // Rate limit: one resend per 5 minutes
        $recent = EmailVerification::where('user_id', $userId)
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 300))
            ->exists();

        if ($recent) {
            $_SESSION['flash_error'] = 'Please wait a few minutes before requesting another verification email.';
            return $this->redirect($response, '/verify-notice');
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->redirect($response, '/login');
        }

        $this->sendVerificationEmail($user);

        $_SESSION['flash_success'] = 'A new verification email has been sent.';
        return $this->redirect($response, '/verify-notice');
    }

    /**
     * Creates a token row and sends the verification email.
     * Called from AuthController after registration.
     */
    public function sendVerificationEmail(User $user): void
    {
        // Delete any existing tokens for this user
        EmailVerification::where('user_id', $user->id)->delete();

        $token = bin2hex(random_bytes(32));

        EmailVerification::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $verifyUrl = (rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/')) . '/verify/' . $token;

        $this->mailer->send(
            $user->email,
            $user->name,
            'Verify your email address',
            'emails/welcome-verify',
            [
                'user'       => $user,
                'verify_url' => $verifyUrl,
                'app_name'   => $_ENV['APP_NAME'] ?? 'Slim Starter',
            ]
        );
    }
}
