<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Servicio para envío de emails de reset de password
 * Funciona independientemente del entorno usando configuración de variables de entorno
 */
class ResetPasswordMailerService
{
    private $logDir;
    private $filesystem;

    public function __construct(string $projectDir)
    {
        $this->logDir = $projectDir . '/var/log';
        $this->filesystem = new Filesystem();
    }

    /**
     * Envía un email de reset de password
     * 
     * @param string $toEmail Email del destinatario
     * @param string $code Código de reset
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function sendResetPasswordEmail(string $toEmail, string $code): array
    {
        try {
            // Obtener configuración desde variables de entorno
            $fromEmail = $_ENV['MAILER_FROM'] ?? 'sistema@troxus.cf';
            $replyToEmail = $_ENV['MAILER_REPLY_TO'] ?? 'noreply@troxus.cf';
            $dsn = $_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN');
            
            if (empty($dsn)) {
                throw new \RuntimeException('MAILER_DSN no está configurado en las variables de entorno');
            }
            
            // Solo deshabilitar verificación SSL en desarrollo
            $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
            if ($isDev && strpos($dsn, 'verify_peer') === false) {
                $separator = strpos($dsn, '?') !== false ? '&' : '?';
                $dsn .= $separator . 'verify_peer=0';
            }
            
            // Log del intento
            $dsnForLog = preg_replace('/:([^:@]+)@/', ':****@', $dsn);
            $this->log("Intentando enviar email - De: {$fromEmail}, Para: {$toEmail}, DSN: {$dsnForLog}");
            
            // Crear transporte y mailer
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);
            
            // Crear email
            $email = (new Email())
                ->from($fromEmail)
                ->to($toEmail)
                ->replyTo($replyToEmail)
                ->priority(Email::PRIORITY_HIGH)
                ->subject('Resetear Password del Sistema Plus Vita')
                ->html($this->getEmailHtml($code));
            
            // Enviar email
            $mailer->send($email);
            
            // Log de éxito
            $this->log("Email de reset password enviado exitosamente - De: {$fromEmail}, Para: {$toEmail}, Código: {$code}");
            
            return [
                'success' => true,
                'message' => 'Email enviado exitosamente',
                'error' => null
            ];
            
        } catch (TransportExceptionInterface $e) {
            $errorMsg = $e->getMessage();
            $this->log("ERROR al enviar email de reset password - Email: {$toEmail}, Error: {$errorMsg}", true);
            
            return [
                'success' => false,
                'message' => 'Error al enviar el correo',
                'error' => $errorMsg
            ];
            
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $this->log("ERROR general al enviar email de reset password - Email: {$toEmail}, Error: {$errorMsg}\nTrace: {$e->getTraceAsString()}", true);
            
            return [
                'success' => false,
                'message' => 'Error general al enviar el correo',
                'error' => $errorMsg
            ];
        }
    }

    /**
     * Genera el HTML del email
     */
    private function getEmailHtml(string $code): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #333;'>Resetear Password del Sistema Plus Vita</h2>
            <p>Use el siguiente código para resetear su password:</p>
            <div style='background-color: #f4f4f4; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;'>
                <h1 style='color: #1d72b8; font-size: 32px; letter-spacing: 5px; margin: 0;'>{$code}</h1>
            </div>
            <p style='color: #666; font-size: 12px;'>Este código expira en 1 hora. Si no solicitó este código, ignore este mensaje.</p>
        </div>";
    }

    /**
     * Escribe en el archivo de log
     */
    private function log(string $message, bool $isError = false): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // Asegurar que el directorio existe
        if (!is_dir($this->logDir)) {
            $this->filesystem->mkdir($this->logDir, 0777, true);
        }
        
        $logFile = $this->logDir . '/reset_password.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // También usar error_log para que aparezca en los logs del sistema
        error_log($logMessage);
    }
}

