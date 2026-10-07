<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sesión PHP con cookies seguras, expiración por inactividad y mensajes flash
 * que viven exactamente una petición (patrón POST → redirección → GET).
 */
final class Session
{
    /** @var array<string, mixed> Datos flash recibidos de la petición anterior. */
    private static array $current = [];

    public static function start(bool $https, string $cookiePath): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = (array) config('app.session');
        $secureSetting = (string) ($config['secure'] ?? 'auto');
        $secure = $secureSetting === 'auto' ? $https : in_array($secureSetting, ['1', 'true', 'yes'], true);

        // use_only_cookies=1 y use_trans_sid=0 ya son los valores por defecto;
        // no se tocan porque PHP 8.4 depreca cambiarlos.
        ini_set('session.use_strict_mode', '1');

        session_name((string) ($config['name'] ?? 'patita_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookiePath === '' ? '/' : $cookiePath,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Cierre por inactividad.
        $idle = max(5, (int) ($config['idle_minutes'] ?? 120)) * 60;
        $last = $_SESSION['_last_activity'] ?? null;
        if (is_int($last) && (time() - $last) > $idle) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last_activity'] = time();

        self::$current = is_array($_SESSION['_flash'] ?? null) ? $_SESSION['_flash'] : [];
        unset($_SESSION['_flash']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    /**
     * Nuevo identificador de sesión conservando los datos (se usa al autenticar).
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Vacía la sesión completa (carrito incluido) y emite un identificador nuevo.
     */
    public static function reset(): void
    {
        $_SESSION = [];
        self::$current = [];
        self::regenerate();
        $_SESSION['_last_activity'] = time();
    }

    // ---------------------------------------------------------------- flash

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash']['messages'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Guarda los datos del formulario (sin contraseñas) y los errores para
     * volver a mostrarlos tras la redirección.
     *
     * @param array<string, mixed>  $input
     * @param array<string, string> $errors
     */
    public static function flashInput(array $input, array $errors = []): void
    {
        $safe = [];
        foreach ($input as $key => $value) {
            if (is_string($value) && !str_contains((string) $key, 'password') && $key !== '_token') {
                $safe[$key] = $value;
            }
        }
        $_SESSION['_flash']['old'] = $safe;
        $_SESSION['_flash']['errors'] = $errors;
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public static function messages(): array
    {
        $messages = self::$current['messages'] ?? [];
        self::$current['messages'] = [];

        return is_array($messages) ? $messages : [];
    }

    public static function old(string $key, string $default = ''): string
    {
        $value = self::$current['old'][$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @return array<string, string>
     */
    public static function errors(): array
    {
        $errors = self::$current['errors'] ?? [];

        return is_array($errors) ? $errors : [];
    }
}
