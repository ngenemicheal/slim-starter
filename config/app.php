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

use App\Extensions\TwigExtension;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

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

    // ── Twig template engine ───────────────────────────────────────────────
    // Injected automatically into every controller that extends Controller.
    Twig::class => function (): Twig {
        $debug     = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $cacheDir  = APP_ROOT . '/storage/cache/twig';

        // Ensure the cache directory exists (Apache / www-data must be able to write)
        if (!$debug && !is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $twig = Twig::create(APP_ROOT . '/views', [
            'cache'       => $debug ? false : $cacheDir,
            'debug'       => $debug,
            'auto_reload' => true,
        ]);

        // Custom functions and filters (session(), flash(), current_path(), etc.)
        $twig->addExtension(new TwigExtension());

        // {{ dump(...) }} available in debug mode
        if ($debug) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        return $twig;
    },

    // ── PHPMailer ─────────────────────────────────────────────────────────
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

    // ── Mailer wrapper ────────────────────────────────────────────────────
    // Wraps PHPMailer with a send(to, name, subject, template, data) helper.
    // Both PHPMailer::class and Twig::class are already in the container,
    // so PHP-DI can autowire this with no extra configuration.
    \App\Mail\Mailer::class => \DI\autowire(),

    // ── Add more service definitions here ─────────────────────────────────
    // App\Services\PostService::class => \DI\autowire(),

];
