<?php

// src/Service/EmailService.php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    // Enviar un correo
    public function sendEmail(string $to, string $subject, string $text): void
    {
        $email = (new Email())
            ->from('reclamos@plusvita.innovateglobal.es')
            ->to($to)
            ->subject($subject)
            ->html($text);

        $this->mailer->send($email);
    }

    // Enviar el mismo correo a múltiples destinatarios
    public function sendEmailToMultipleRecipients(array $recipients, string $subject, string $text): void
    {
        $email = (new Email())
            ->from('reclamos@plusvita.innovateglobal.es')
            ->subject($subject)
            ->html($text);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        $var = $this->mailer->send($email);
    }

    // Enviar diferentes correos a diferentes destinatarios
    public function sendDifferentEmails(array $emails): void
    {
        foreach ($emails as $emailData) {
            $email = (new Email())
                ->from('reclamos@plusvita.innovateglobal.es')
                ->to($emailData['to'])
                ->subject($emailData['subject'])
                ->html($emailData['text']);

            $this->mailer->send($email);
        }
    }
}

