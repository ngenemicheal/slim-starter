<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Slim\Views\Twig;

class Mailer
{
    public function __construct(
        private PHPMailer $phpMailer,
        private Twig $twig
    ) {}

    /**
     * Send an HTML email rendered from a Twig template.
     *
     * @param string $toAddress Recipient email
     * @param string $toName    Recipient name
     * @param string $subject   Email subject
     * @param string $template  Template path relative to views/ without extension (e.g. 'emails/welcome-verify')
     * @param array  $data      Variables passed to the template
     */
    public function send(
        string $toAddress,
        string $toName,
        string $subject,
        string $template,
        array $data = []
    ): void {
        $html = $this->twig->getEnvironment()->render($template . '.twig', $data);

        $mail = clone $this->phpMailer;
        $mail->clearAllRecipients();
        $mail->clearAttachments();

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

        $mail->addAddress($toAddress, $toName);
        $mail->send();
    }
}
