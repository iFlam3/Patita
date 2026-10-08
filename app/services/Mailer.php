<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Envío de correo de texto plano.
 *
 * - MAIL_TRANSPORT=log: guarda cada mensaje como .eml en storage/mail
 *   (desarrollo). La carpeta está bloqueada para la web; el enlace de
 *   recuperación nunca se muestra en pantalla.
 * - MAIL_TRANSPORT=smtp: envía con App\Services\SmtpClient (STARTTLS o SSL,
 *   AUTH LOGIN), sin dependencias externas.
 */
final class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $body): void
    {
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Dirección de correo inválida.');
        }

        $config = (array) config('app.mail');
        $toName = self::clean($toName);
        $subject = self::clean($subject);

        match ((string) $config['transport']) {
            'smtp' => self::sendSmtp($config, $toEmail, $toName, $subject, $body),
            'log' => self::writeToFile($config, $toEmail, $toName, $subject, $body),
            default => throw new RuntimeException('MAIL_TRANSPORT debe ser "log" o "smtp".'),
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function writeToFile(array $config, string $toEmail, string $toName, string $subject, string $body): void
    {
        $from = self::clean((string) $config['from']);
        $fromName = self::clean((string) $config['from_name']);

        $message = implode("\r\n", [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $from . '>',
            'To: ' . mb_encode_mimeheader($toName, 'UTF-8') . ' <' . $toEmail . '>',
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            str_replace(["\r\n", "\n"], "\r\n", $body),
        ]);

        $file = BASE_PATH . '/storage/mail/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
        if (file_put_contents($file, $message, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo guardar el correo en storage/mail.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function sendSmtp(array $config, string $toEmail, string $toName, string $subject, string $body): void
    {
        (new SmtpClient(
            (string) $config['host'],
            (int) $config['port'],
            (string) $config['encryption'],
            (string) $config['username'],
            (string) $config['password'],
        ))->send(
            self::clean((string) $config['from']),
            self::clean((string) $config['from_name']),
            $toEmail,
            $toName,
            $subject,
            $body
        );
    }

    /**
     * Quita saltos de línea para impedir inyección de cabeceras.
     */
    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }
}
