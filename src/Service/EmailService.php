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
            ->from('reclamos@troxus.cf')
            ->to($to)
            ->subject($subject)
            ->html($text);

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                throw new \RuntimeException('Error sending email: ' . $e->getMessage());
            }
    }

    // Enviar el mismo correo a múltiples destinatarios
    public function sendEmailToMultipleRecipients(array $recipients, string $subject, string $text): void
    {
        $email = (new Email())
            ->from('reclamos@troxus.cf')
            ->subject($subject)
            ->html($text);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error sending email: ' . $e->getMessage());
        }
    }

    // Enviar diferentes correos a diferentes destinatarios
    public function sendDifferentEmails(array $emails): void
    {
        foreach ($emails as $emailData) {
            $email = (new Email())
                ->from('reclamos@troxus.cf')
                ->to($emailData['to'])
                ->subject($emailData['subject'])
                ->html($emailData['text']);

            $this->mailer->send($email);
        }
    }
}

