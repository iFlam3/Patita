<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Tokens de recuperación. Solo se guarda hash('sha256', token).
 */
final class PasswordResetToken
{
    public static function create(int $userId, string $tokenHash, int $ttlMinutes): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $tokenHash, Database::now($ttlMinutes * 60), Database::now()]);
    }

    /**
     * Invalida los tokens pendientes del usuario (solo el último enlace sirve).
     */
    public static function invalidateForUser(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE password_reset_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL'
        );
        $stmt->execute([Database::now(), $userId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findValid(string $tokenHash): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?'
        );
        $stmt->execute([$tokenHash, Database::now()]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Marca el token como usado. Devuelve false si otra petición lo usó antes
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
