<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([self::normalizeEmail($email)]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = ?';
        $params = [self::normalizeEmail($email)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public static function create(string $name, string $email, string $passwordHash, string $role = 'customer'): int
    {
        $now = Database::now();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, self::normalizeEmail($email), $passwordHash, $role, $now, $now]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET name = ?, email = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, self::normalizeEmail($email), Database::now(), $id]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$passwordHash, Database::now(), $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET role = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$role, Database::now(), $id]);
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }
}
