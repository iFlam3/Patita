<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

final class Order
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByCheckoutToken(string $token): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE checkout_token = ?');
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forUser(int $userId, int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT o.*, (SELECT COALESCE(SUM(quantity), 0) FROM order_items i WHERE i.order_id = o.id) AS units
             FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Último pedido del usuario, para precargar los datos de entrega.
     *
     * @return array<string, mixed>|null
     */
    public static function latestForUser(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function items(int $orderId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, p.slug AS product_slug, p.active AS product_active, p.image_url, p.pet_type, p.category
             FROM order_items i
             LEFT JOIN products p ON p.id = i.product_id
             WHERE i.order_id = ? ORDER BY i.id'
        );
        $stmt->execute([$orderId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function adminList(string $status, int $page, int $perPage = 20): array
    {
        $where = '1 = 1';
        $params = [];
        if ($status !== '' && array_key_exists($status, (array) config('catalog.order_statuses'))) {
            $where = 'o.status = ?';
            $params[] = $status;
        }
        $pdo = Database::pdo();

        $count = $pdo->prepare('SELECT COUNT(*) FROM orders o WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $stmt = $pdo->prepare(
            'SELECT o.* FROM orders o WHERE ' . $where . ' ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?'
        );
        $index = 1;
        foreach ($params as $value) {
            $stmt->bindValue($index++, $value);
        }
        $stmt->bindValue($index++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($index, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /**
     * @return array<string, int>
     */
    public static function countsByStatus(): array
    {
        $rows = Database::pdo()->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Estados a los que puede pasar un pedido. Entregado y cancelado son finales.
     *
     * @return list<string>
     */
    public static function allowedTransitions(string $current): array
    {
        $statuses = (array) config('catalog.order_statuses');
        if (!isset($statuses[$current]) || $statuses[$current]['final'] === true) {
            return [];
        }

        return array_values(array_filter(array_keys($statuses), static fn (string $s): bool => $s !== $current));
    }

    /**
     * Cambia el estado. Al cancelar, devuelve al stock las unidades de los
     * productos que aún existen; todo dentro de una transacción con bloqueo.
     */
    public static function updateStatus(int $id, string $newStatus): void
    {
        Database::transaction(static function (PDO $pdo) use ($id, $newStatus): void {
            $stmt = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!is_array($order)) {
                throw new RuntimeException('El pedido no existe.');
            }
            if (!in_array($newStatus, self::allowedTransitions((string) $order['status']), true)) {
                throw new RuntimeException('Ese cambio de estado no está permitido.');
            }

            if ($newStatus === 'cancelled') {
                $restock = $pdo->prepare(
                    'UPDATE products p
                     JOIN order_items i ON i.product_id = p.id
                     SET p.stock = p.stock + i.quantity, p.updated_at = ?
                     WHERE i.order_id = ?'
                );
                $restock->execute([Database::now(), $id]);
            }

            $update = $pdo->prepare('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?');
            $update->execute([$newStatus, Database::now(), $id]);
        });
    }

    /**
     * Decodifica la dirección guardada como JSON.
     *
     * @param array<string, mixed> $order
     * @return array<string, string>
     */
    public static function address(array $order): array
    {
        $data = json_decode((string) $order['shipping_address'], true);
        if (!is_array($data)) {
            return ['line1' => (string) $order['shipping_address']];
        }

        return array_map('strval', array_filter($data, 'is_scalar'));
    }
}
