<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use PDO;
use PDOException;
use Throwable;

/**
 * Crea pedidos de forma segura:
 *  1. Si el checkout_token ya generó un pedido, lo devuelve (idempotente).
 *  2. Dentro de una transacción bloquea las filas de los productos
 *     (SELECT ... FOR UPDATE, en orden de id para evitar interbloqueos).
 *  3. Recalcula precios y totales desde la base de datos y verifica que
 *     coincidan con lo que el cliente revisó (huella del carrito).
 *  4. Descuenta stock con UPDATE ... WHERE stock >= cantidad.
 *  5. Inserta el pedido y sus líneas. Si algo falla, se revierte todo.
 * El índice UNIQUE de checkout_token es la última barrera contra duplicados.
 */
final class CheckoutService
{
    /**
     * @param array<string, mixed> $user
     * @param array{name: string, email: string, phone: string, address: array<string, string>, payment_method: string} $data
     * @return array{id: int, duplicate: bool}
     */
    public static function placeOrder(array $user, array $data, string $checkoutToken, string $reviewedFingerprint): array
    {
        $userId = (int) $user['id'];

        $existing = Order::findByCheckoutToken($checkoutToken);
        if ($existing !== null) {
            if ((int) $existing['user_id'] !== $userId) {
                throw new CheckoutException('No pudimos confirmar el pedido. Vuelve a revisar tu carrito.');
            }

            return ['id' => (int) $existing['id'], 'duplicate' => true];
        }

        $cart = Cart::raw();
        if ($cart === []) {
            throw new CheckoutException('Tu carrito está vacío.');
        }

        $currency = (string) config('app.currency');

        try {
            $orderId = Database::transaction(static function (PDO $pdo) use ($cart, $currency, $userId, $data, $checkoutToken, $reviewedFingerprint): int {
                $ids = array_keys($cart);
                sort($ids);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare(
                    'SELECT id, sku, name, price, currency, stock, active FROM products
                     WHERE id IN (' . $placeholders . ') ORDER BY id FOR UPDATE'
                );
                $stmt->execute($ids);
                $rows = [];
                foreach ($stmt->fetchAll() as $row) {
                    $rows[(int) $row['id']] = $row;
                }

                $problems = [];
                $lines = [];
                $subtotal = 0;
                foreach ($ids as $id) {
                    $qty = $cart[$id];
                    $product = $rows[$id] ?? null;
                    if ($product === null || !Cart::isSellable($product)) {
                        $problems[] = sprintf('"%s" ya no está disponible.', (string) ($product['name'] ?? 'Un producto'));
                        continue;
                    }
                    if ((int) $product['stock'] < $qty) {
                        $problems[] = sprintf('"%s": quedan %d unidad(es) y pediste %d.', (string) $product['name'], (int) $product['stock'], $qty);
                        continue;
                    }
                    $unit = to_cents((string) $product['price']);
                    $lines[] = ['id' => $id, 'sku' => (string) $product['sku'], 'name' => (string) $product['name'], 'qty' => $qty, 'unit' => $unit];
                    $subtotal += $unit * $qty;
                }

                if ($problems !== []) {
                    throw new CheckoutException('Algunas existencias cambiaron. Revisa tu carrito antes de confirmar.', $problems);
                }

                $shipping = Cart::shippingFor($subtotal);
                $fingerprint = Cart::fingerprint(
                    array_map(static fn (array $l): array => [$l['id'], $l['qty'], $l['unit']], $lines),
                    $shipping
                );
                if (!hash_equals($reviewedFingerprint, $fingerprint)) {
                    throw new CheckoutException('Tu carrito cambió desde que lo revisaste (precio, cantidad o envío). Revisa el resumen actualizado y confirma de nuevo.');
                }

                $now = Database::now();
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders (user_id, status, subtotal, shipping_cost, total, currency, customer_name, customer_email,
                        customer_phone, shipping_address, payment_method, checkout_token, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertOrder->execute([
                    $userId,
                    'received',
                    cents_to_decimal($subtotal),
                    cents_to_decimal($shipping),
                    cents_to_decimal($subtotal + $shipping),
                    $currency,
                    $data['name'],
                    $data['email'],
                    $data['phone'],
                    json_encode($data['address'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $data['payment_method'],
                    $checkoutToken,
                    $now,
                    $now,
                ]);
                $orderId = (int) $pdo->lastInsertId();

                $decrement = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = ? WHERE id = ? AND stock >= ?');
                $insertItem = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                foreach ($lines as $line) {
                    $decrement->execute([$line['qty'], $now, $line['id'], $line['qty']]);
                    if ($decrement->rowCount() !== 1) {
                        throw new CheckoutException(sprintf('No hay existencias suficientes de "%s".', $line['name']));
                    }
                    $insertItem->execute([
                        $orderId,
                        $line['id'],
                        $line['name'],
                        $line['sku'],
                        $line['qty'],
                        cents_to_decimal($line['unit']),
                        cents_to_decimal($line['unit'] * $line['qty']),
                    ]);
                }

                return $orderId;
            });
        } catch (PDOException $e) {
            // Carrera entre dos envíos con el mismo token: el segundo choca con el UNIQUE.
            if ((string) $e->getCode() === '23000') {
                $existing = Order::findByCheckoutToken($checkoutToken);
                if ($existing !== null && (int) $existing['user_id'] === $userId) {
                    return ['id' => (int) $existing['id'], 'duplicate' => true];
                }
            }
            throw $e;
        }

        Cart::clear();
        self::sendConfirmation($orderId);

        return ['id' => $orderId, 'duplicate' => false];
    }

    /**
     * Correo de confirmación. Un fallo de correo no anula el pedido.
     */
    private static function sendConfirmation(int $orderId): void
    {
        try {
            $order = Order::find($orderId);
            if ($order === null) {
                return;
            }
            $items = Order::items($orderId);
            $currency = (string) $order['currency'];

            $lines = [];
            foreach ($items as $item) {
                $lines[] = sprintf(
                    '- %s x%d: %s',
                    (string) $item['product_name'],
                    (int) $item['quantity'],
                    format_money(to_cents((string) $item['subtotal']), $currency)
                );
            }

            $payment = (string) $order['payment_method'] === 'bank_transfer'
                ? "Pago por transferencia bancaria (modalidad demostrativa).\n" . (string) config('app.shop.bank_details')
                . "\nUsa " . order_number($orderId) . ' como referencia.'
                : 'Pago contra entrega (modalidad demostrativa): pagas al recibir tu pedido.';

            $body = sprintf(
                "Hola, %s:\n\nRecibimos tu pedido %s.\n\n%s\n\nSubtotal: %s\nEnvío: %s\nTotal: %s\n\n%s\n\nPuedes ver el estado en: %s\n\n%s",
                (string) $order['customer_name'],
                order_number($orderId),
                implode("\n", $lines),
                format_money(to_cents((string) $order['subtotal']), $currency),
                format_money(to_cents((string) $order['shipping_cost']), $currency),
                format_money(to_cents((string) $order['total']), $currency),
                $payment,
                absolute_url('/cuenta/pedidos/' . $orderId),
                (string) config('app.name')
            );

            Mailer::send((string) $order['customer_email'], (string) $order['customer_name'], 'Recibimos tu pedido ' . order_number($orderId), $body);
        } catch (Throwable $e) {
            log_message('mail', 'No se pudo enviar la confirmación del pedido.', ['order' => $orderId, 'error' => $e->getMessage()]);
        }
    }
}
