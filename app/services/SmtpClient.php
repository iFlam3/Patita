<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Cliente SMTP mínimo (RFC 5321) para correos de texto plano.
 *
 * - encryption = tls  : conexión en claro y STARTTLS (puerto 587).
 * - encryption = ssl  : TLS implícito desde el inicio (puerto 465).
 * - encryption = none : sin cifrado (solo servidores locales de prueba).
 * Verifica el certificado del servidor y nunca envía credenciales sin cifrar
 * salvo con "none". La contraseña no se registra en ningún log.
 */
final class SmtpClient
{
    private const TIMEOUT = 20;

    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private string $host,
        private int $port,
        private string $encryption,
        private string $username,
        private string $password,
    ) {
        if ($this->host === '') {
            throw new RuntimeException('Falta SMTP_HOST en .env.');
        }
    }

    public function send(string $from, string $fromName, string $to, string $toName, string $subject, string $body): void
    {
        try {
            $this->connect();
            $this->expect(220);
            $this->ehlo();

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('No se pudo iniciar TLS con el servidor SMTP.');
                }
                $this->ehlo();
            }

            if ($this->username !== '') {
                $this->command('AUTH LOGIN', 334);
                $this->command(base64_encode($this->username), 334);
                $this->command(base64_encode($this->password), 235, 'El servidor SMTP rechazó el usuario o la contraseña.');
            }

            $this->command('MAIL FROM:<' . $from . '>', 250);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', 354);
            $this->write($this->message($from, $fromName, $to, $toName, $subject, $body) . "\r\n.\r\n");
            $this->expect(250);
            $this->command('QUIT', 221);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    private function connect(): void
    {
        $scheme = $this->encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create(['ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'peer_name'         => $this->host,
        ]]);
        $socket = @stream_socket_client(
            $scheme . '://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if ($socket === false) {
            throw new RuntimeException(sprintf('No se pudo conectar a %s:%d (%s).', $this->host, $this->port, $errstr));
        }
        stream_set_timeout($socket, self::TIMEOUT);
        $this->socket = $socket;
    }

    private function ehlo(): void
    {
        $domain = preg_replace('/[^a-z0-9.-]/i', '', (string) (gethostname() ?: 'localhost')) ?: 'localhost';
        $this->command('EHLO ' . $domain, 250);
    }

    /**
     * @param int|list<int> $expected
     */
    private function command(string $line, int|array $expected, string $error = ''): string
    {
        $this->write($line . "\r\n");

        return $this->expect($expected, $error);
    }

    /**
     * Lee una respuesta (posiblemente multilínea) y valida su código.
     *
     * @param int|list<int> $expected
     */
    private function expect(int|array $expected, string $error = ''): string
    {
        $response = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, (array) $expected, true)) {
            $detail = trim(preg_replace('/\s+/', ' ', $response) ?? '');
            throw new RuntimeException($error !== '' ? $error . ' (' . $detail . ')' : 'Respuesta SMTP inesperada: ' . ($detail !== '' ? $detail : 'sin respuesta'));
        }

        return $response;
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket) || fwrite($this->socket, $data) === false) {
            throw new RuntimeException('Se perdió la conexión con el servidor SMTP.');
        }
    }

    private function message(string $from, string $fromName, string $to, string $toName, string $subject, string $body): string
    {
        $domain = substr((string) strrchr($from, '@'), 1) ?: 'localhost';
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->address($from, $fromName),
            'To: ' . $this->address($to, $toName),
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n"),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        // base64 en líneas de 76 caracteres: ninguna línea empieza con "." ni excede el límite.
        return implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode(str_replace("\n", "\r\n", $body)), 76, "\r\n"));
    }

    private function address(string $email, string $name): string
    {
        return $name !== '' ? mb_encode_mimeheader($name, 'UTF-8', 'B') . ' <' . $email . '>' : '<' . $email . '>';
    }
}
