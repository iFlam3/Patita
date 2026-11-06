<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Códigos de recuperación de 6 dígitos. En token_hash se guarda
 * password_hash(código) (bcrypt, 60 caracteres): con solo 10^6 combinaciones,
 * un SHA-256 sin sal se revertiría al instante si se filtrara la base.
 */
final class PasswordResetToken
{
    public static function newCode(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    public static function create(int $userId, string $code, int $ttlMinutes): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, password_hash($code, PASSWORD_BCRYPT), Database::now($ttlMinutes * 60), Database::now()]);
    }

    /**
     * Invalida los códigos pendientes del usuario (solo el último sirve).
     */
    public static function invalidateForUser(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE password_reset_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL'
        );
        $stmt->execute([Database::now(), $userId]);
    }

    /**
     * Último código vigente del usuario.
     *
     * @return array<string, mixed>|null
     */
    public static function latestValidForUser(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL AND expires_at > ?
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId, Database::now()]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Marca el código como usado. Devuelve false si otra petición lo usó antes
     * (garantiza un solo uso aun con solicitudes simultáneas).
     */
    public static function markUsed(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE password_reset_tokens SET used_at = ? WHERE id = ? AND used_at IS NULL AND expires_at > ?'
        );
        $stmt->execute([Database::now(), $id, Database::now()]);

        return $stmt->rowCount() === 1;
    }

    public static function purgeExpired(): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM password_reset_tokens WHERE expires_at < ?');
        $stmt->execute([Database::now(-7 * 86400)]);
    }
}
