<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

/**
 * Carrito en la sesión PHP: solo guarda [product_id => cantidad].
 * Precios, existencias y totales se leen siempre de la base de datos;
 * nunca se usa un precio enviado por el navegador.
 */
final class Cart
{
    private const KEY = 'cart';
    private const MAX_LINES = 50;

    /**
     * @return array<int, int>
     */
    public static function raw(): array
    {
        $cart = $_SESSION[self::KEY] ?? [];
        if (!is_array($cart)) {
            return [];
        }
        $clean = [];
        foreach ($cart as $id => $qty) {
            if (is_int($id) && is_int($qty) && $id > 0 && $qty > 0) {
                $clean[$id] = $qty;
            }
        }

        return $clean;
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /**
     * @return array{ok: bool, message: string, quantity: int, count: int, name: string}
     */
    public static function add(int $productId, int $quantity): array
    {
        $quantity = max(1, $quantity);
        $product = self::purchasable($productId);
        if ($product === null) {
            return self::result(false, 'Este producto no está disponible.', 0, '');
        }

        $name = (string) $product['name'];
        $stock = (int) $product['stock'];
        if ($stock <= 0) {
            return self::result(false, 'Este producto está agotado por ahora.', 0, $name);
        }

        $cart = self::raw();
        if (!isset($cart[$productId]) && count($cart) >= self::MAX_LINES) {
            return self::result(false, 'Tu carrito alcanzó el máximo de productos distintos.', 0, $name);
        }

        $current = $cart[$productId] ?? 0;
        $allowed = min($stock, self::maxPerItem());
        if ($current >= $allowed) {
            return self::result(false, sprintf('Ya tienes en el carrito el máximo disponible (%d).', $allowed), $current, $name);
        }

        $new = min($current + $quantity, $allowed);
        $cart[$productId] = $new;
        self::store($cart);

        $message = $new < $current + $quantity
            ? sprintf('Agregamos %d unidad(es) de "%s": es el máximo disponible.', $new - $current, $name)
            : sprintf('Agregaste "%s" al carrito.', $name);

        return self::result(true, $message, $new, $name);
    }

    /**
     * Fija la cantidad de una línea. 0 elimina el producto.
     *
     * @return array{ok: bool, message: string, quantity: int, count: int, name: string}
     */
    public static function update(int $productId, int $quantity): array
    {
        $cart = self::raw();
        if (!isset($cart[$productId])) {
            return self::result(false, 'Ese producto ya no está en tu carrito.', 0, '');
        }
        if ($quantity <= 0) {
            self::remove($productId);

            return self::result(true, 'Quitaste el producto del carrito.', 0, '');
        }

        $product = self::purchasable($productId);
        if ($product === null || (int) $product['stock'] <= 0) {
            self::remove($productId);

            return self::result(false, 'El producto ya no está disponible y lo quitamos del carrito.', 0, '');
        }

        $allowed = min((int) $product['stock'], self::maxPerItem());
        $cart[$productId] = min($quantity, $allowed);
        self::store($cart);

        $message = $quantity > $allowed
            ? sprintf('Solo puedes llevar %d unidad(es) de "%s".', $allowed, (string) $product['name'])
            : 'Actualizamos la cantidad.';

        return self::result(true, $message, $cart[$productId], (string) $product['name']);
    }

    public static function remove(int $productId): void
    {
        $cart = self::raw();
        unset($cart[$productId]);
        self::store($cart);
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    /**
     * Calcula el carrito con datos frescos de la base de datos y lo normaliza:
     * retira productos inactivos o agotados y ajusta cantidades al stock.
     *
     * @return array{
     *   lines: list<array{product: array<string, mixed>, quantity: int, unit_cents: int, total_cents: int, max: int}>,
     *   notices: list<string>, subtotal_cents: int, shipping_cents: int, total_cents: int,
     *   currency: string, count: int, free_shipping_remaining_cents: int, fingerprint: string
     * }
     */
    public static function summary(): array
    {
        $cart = self::raw();
        $currency = (string) config('app.currency');
        $lines = [];
        $notices = [];
        $subtotal = 0;

        if ($cart !== []) {
            $products = Product::findMany(array_keys($cart));
            foreach ($cart as $id => $qty) {
                $product = $products[$id] ?? null;
                if ($product === null || !self::isSellable($product)) {
                    unset($cart[$id]);
                    $notices[] = $product !== null
                        ? sprintf('"%s" ya no está disponible y lo quitamos de tu carrito.', (string) $product['name'])
                        : 'Quitamos de tu carrito un producto que ya no existe.';
                    continue;
                }

                $stock = (int) $product['stock'];
                if ($stock <= 0) {
                    unset($cart[$id]);
                    $notices[] = sprintf('"%s" se agotó y lo quitamos de tu carrito.', (string) $product['name']);
                    continue;
                }

                $allowed = min($stock, self::maxPerItem());
                if ($qty > $allowed) {
                    $qty = $allowed;
                    $cart[$id] = $qty;
                    $notices[] = sprintf('Ajustamos "%s" a %d unidad(es) según la disponibilidad.', (string) $product['name'], $qty);
                }

                $unit = to_cents((string) $product['price']);
                $lines[] = [
                    'product'     => $product,
                    'quantity'    => $qty,
                    'unit_cents'  => $unit,
                    'total_cents' => $unit * $qty,
                    'max'         => $allowed,
                ];
                $subtotal += $unit * $qty;
            }
            self::store($cart);
        }

        $shipping = self::shippingFor($subtotal);
        $freeFrom = self::freeShippingFrom();

        return [
            'lines'                         => $lines,
            'notices'                       => $notices,
            'subtotal_cents'                => $subtotal,
            'shipping_cents'                => $shipping,
            'total_cents'                   => $subtotal + $shipping,
            'currency'                      => $currency,
            'count'                         => array_sum($cart),
            'free_shipping_remaining_cents' => ($freeFrom > 0 && $subtotal > 0 && $subtotal < $freeFrom) ? $freeFrom - $subtotal : 0,
            'fingerprint'                   => self::fingerprint(array_map(
                static fn (array $l): array => [(int) $l['product']['id'], $l['quantity'], $l['unit_cents']],
                $lines
            ), $shipping),
        ];
    }

    /**
     * Huella del contenido revisado: si cambian productos, cantidades, precios
     * o envío entre la revisión y la confirmación, el pedido no se crea.
     *
     * @param list<array{0: int, 1: int, 2: int}> $lines [id, cantidad, precio en centavos]
     */
    public static function fingerprint(array $lines, int $shippingCents): string
    {
        usort($lines, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return hash('sha256', (string) json_encode([$lines, $shippingCents]));
    }

    public static function shippingFor(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }
        $free = self::freeShippingFrom();
        if ($free > 0 && $subtotalCents >= $free) {
            return 0;
        }

        return to_cents((string) config('app.shop.shipping_flat_rate', '0'));
    }

    public static function freeShippingFrom(): int
    {
        $value = (string) config('app.shop.shipping_free_from', '0');

        return $value === '' ? 0 : to_cents($value);
    }

    public static function maxPerItem(): int
    {
        return (int) config('app.shop.max_qty_per_item', 20);
    }

    /**
     * @param array<string, mixed> $product
     */
    public static function isSellable(array $product): bool
    {
        return (int) $product['active'] === 1
            && (string) $product['currency'] === (string) config('app.currency')
            && to_cents((string) $product['price']) > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function purchasable(int $productId): ?array
    {
        $product = Product::find($productId);

        return ($product !== null && self::isSellable($product)) ? $product : null;
    }

    /**
     * @param array<int, int> $cart
     */
    private static function store(array $cart): void
    {
        if ($cart === []) {
            unset($_SESSION[self::KEY]);

            return;
        }
        $_SESSION[self::KEY] = $cart;
    }

    /**
     * @return array{ok: bool, message: string, quantity: int, count: int, name: string}
     */
    private static function result(bool $ok, string $message, int $quantity, string $name): array
    {
        return ['ok' => $ok, 'message' => $message, 'quantity' => $quantity, 'count' => self::count(), 'name' => $name];
    }
}
