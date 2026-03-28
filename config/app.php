<?php

/**
 * PHP-DI container definitions.
 *
 * Anything returned here becomes injectable via constructor or
 * via $container->get(ClassName::class).
 *
 * Add your own service bindings below.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;

return [

    // ── Application settings ───────────────────────────────────────────────
    'settings' => [
        'name'   => $_ENV['APP_NAME']  ?? 'Slim Starter',
        'env'    => $_ENV['APP_ENV']   ?? 'development',
        'debug'  => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url'    => $_ENV['APP_URL']   ?? 'http://localhost',
        'secret' => $_ENV['APP_SECRET'] ?? 'change-this-secret',
        'mode'   => strtolower($_ENV['APP_MODE'] ?? 'web'),
    ],

    // ── PHPMailer ─────────────────────────────────────────────────────────
    // Inject PHPMailer into any controller or service via type-hinted constructor.
    PHPMailer::class => function (ContainerInterface $c): PHPMailer {
        $encryption = $_ENV['MAIL_ENCRYPTION'] ?? '';
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host     = $_ENV['MAIL_HOST']     ?? 'localhost';
        $mail->Port     = (int) ($_ENV['MAIL_PORT'] ?? 1025);
        $mail->SMTPAuth = !empty($_ENV['MAIL_USERNAME']);

        if ($mail->SMTPAuth) {
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = $encryption ?: PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'] ?? 'hello@example.com',
            $_ENV['MAIL_FROM_NAME']    ?? 'Slim Starter'
        );

        return $mail;
    },

    // ── Add more service definitions here ─────────────────────────────────
    // e.g.:
    // App\Services\UserService::class => \DI\autowire(),

];
