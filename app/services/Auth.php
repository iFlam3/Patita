<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Csrf;
use App\Core\Session;
use App\Models\User;

/**
 * Usuario autenticado de la sesión.
 *
 * La sesión guarda el id y una huella del hash de contraseña. Si la contraseña
 * cambia (cambio o recuperación), la huella deja de coincidir y las demás
 * sesiones abiertas de ese usuario se cierran en su siguiente petición.
 */
final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;
    private static bool $resolved = false;

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $auth = Session::get('auth');
        if (!is_array($auth) || !isset($auth['id'], $auth['fp'])) {
            return null;
        }

        $user = User::find((int) $auth['id']);
        if ($user === null || !hash_equals((string) $auth['fp'], self::fingerprint($user))) {
            Session::forget('auth');

            return null;
        }

        self::$user = $user;

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'admin';
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('auth', ['id' => (int) $user['id'], 'fp' => self::fingerprint($user)]);
        Csrf::rotate();
        self::$user = $user;
        self::$resolved = true;
    }

    /**
     * Actualiza la huella tras cambiar datos propios (mantiene esta sesión).
     *
     * @param array<string, mixed> $user
     */
    public static function refresh(array $user): void
    {
        Session::regenerate();
        Session::set('auth', ['id' => (int) $user['id'], 'fp' => self::fingerprint($user)]);
        self::$user = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::reset();
        Csrf::rotate();
        self::$user = null;
        self::$resolved = true;
    }

    /**
     * @param array<string, mixed> $user
     */
    private static function fingerprint(array $user): string
    {
        return hash('sha256', (string) $user['password_hash']);
    }
}
