<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Token CSRF sincronizado con la sesión. Se exige en toda petición POST
 * (campo _token o cabecera X-CSRF-Token) y se rota al iniciar o cerrar sesión.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        $token = $_SESSION[self::KEY] ?? null;
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::KEY] = $token;
        }

        return $token;
    }

    public static function verify(?string $token): bool
    {
        $expected = $_SESSION[self::KEY] ?? null;

        return is_string($expected) && is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    public static function rotate(): void
    {
        $_SESSION[self::KEY] = bin2hex(random_bytes(32));
    }
}
