<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Petición HTTP actual con acceso tipado (solo cadenas) a GET y POST.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        public string $method,
        public string $path,
        private array $query,
        private array $post,
        private array $server,
    ) {
    }

    public static function capture(string $basePath): self
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = is_string($uri) ? $uri : '/';

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, strlen('/index.php'));
        }

        $path = '/' . trim(rawurldecode($uri), '/');

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    /**
     * Valor POST como cadena recortada. Los arreglos se ignoran.
     */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $default;

        return is_string($value) ? trim(self::utf8($value)) : $default;
    }

    /**
     * Valor POST sin recortar (contraseñas).
     */
    public function raw(string $key): string
    {
        $value = $this->post[$key] ?? '';

        return is_string($value) ? self::utf8($value) : '';
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim(self::utf8($value)) : $default;
    }

    /**
     * Reemplaza secuencias UTF-8 inválidas y caracteres de control (salvo
     * saltos de línea y tabulador) para que nunca lleguen a la base de datos
     * ni rompan json_encode.
     */
    private static function utf8(string $value): string
    {
        $value = mb_check_encoding($value, 'UTF-8') ? $value : mb_scrub($value, 'UTF-8');

        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }

    /**
     * Archivo subido (entrada de $_FILES) o null si no se envió ninguno.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        if (!is_array($file) || is_array($file['name'] ?? null)
            || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /**
     * @return array<string, mixed>
     */
    public function allInput(): array
    {
        return $this->post;
    }

    /**
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept'), 'application/json');
    }

    public function isFetch(): bool
    {
        return $this->header('X-Requested-With') === 'fetch';
    }

    /**
     * IP del cliente. Solo se usa REMOTE_ADDR: detrás de un proxy inverso hay
     * que configurar el servidor web para que REMOTE_ADDR sea la IP real
     * (no se confía en X-Forwarded-For porque el cliente puede falsificarla).
     */
    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($ip) ? $ip : '0.0.0.0';
    }

    public function isHttps(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return (is_string($https) && $https !== '' && strtolower($https) !== 'off')
            || (string) ($this->server['SERVER_PORT'] ?? '') === '443';
    }
}
