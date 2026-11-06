<?php

declare(strict_types=1);

/*
 * Genera ~6 meses de pedidos FICTICIOS para probar las métricas y la
 * proyección de ventas del panel. Todo queda marcado para poder borrarlo:
 *   - cliente: ventas-demo@patita.test (sin contraseña utilizable)
 *   - pedidos: checkout_token empieza por "demo"
 *
 * Uso:
 *   php scripts/seed_sales_demo.php            escribe database/sales_demo.sql
 *   php scripts/seed_sales_demo.php --apply    además lo ejecuta en la base local
 *   php scripts/seed_sales_demo.php --clear    borra los pedidos demo de la base local
 *
 * En InfinityFree: importa database/sales_demo.sql en phpMyAdmin (pestaña SQL).
 * Las fechas usan NOW() - INTERVAL, así el archivo sirve en cualquier fecha.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

const DEMO_EMAIL = 'ventas-demo@patita.test';
const CLEAR_SQL = "DELETE FROM orders WHERE checkout_token LIKE 'demo%';\n"
    . "DELETE FROM users WHERE email = '" . DEMO_EMAIL . "' AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = users.id);\n";

$options = getopt('', ['apply', 'clear']);
$pdo = Database::pdo();

$run = static function (string $sql) use ($pdo): void {
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
        $pdo->exec($statement);
    }
};

if (isset($options['clear'])) {
    $run(CLEAR_SQL);
    echo "Pedidos de demostración borrados.\n";
    exit(0);
}

$products = $pdo->query("SELECT sku, name, price FROM products WHERE active = 1 AND data_origin = 'demo' ORDER BY id")->fetchAll();
if ($products === []) {
    fwrite(STDERR, "No hay productos demo en la base. Ejecuta primero el instalador.\n");
    exit(1);
}

mt_srand(20261106); // mismo archivo en cada ejecución
$q = static fn (string $v): string => $pdo->quote($v);
$names = ['María González', 'José Rodríguez', 'Ana Pérez', 'Luis Hernández', 'Carla Martínez', 'Pedro Díaz',
    'Valentina Rojas', 'Andrés Silva', 'Gabriela Torres', 'Daniel Moreno', 'Sofía Ramírez', 'Miguel Castillo'];
$cities = ['Caracas', 'Maracaibo', 'Valencia', 'Barquisimeto', 'Mérida', 'Puerto La Cruz'];
$flat = to_cents((string) config('app.shop.shipping_flat_rate'));
$freeFrom = to_cents((string) config('app.shop.shipping_free_from'));
$currency = (string) config('app.currency');

$sql = "-- Ventas FICTICIAS para el panel de métricas (generado por scripts/seed_sales_demo.php).\n"
    . "-- Para borrarlas: DELETE FROM orders WHERE checkout_token LIKE 'demo%';\n\n"
    . CLEAR_SQL
    . "INSERT IGNORE INTO users (name, email, password_hash, role) VALUES ('Cliente de demostración', '" . DEMO_EMAIL . "', '!sin-acceso', 'customer');\n"
    . "SET @demo_user = (SELECT id FROM users WHERE email = '" . DEMO_EMAIL . "');\n";

$count = 0;
for ($daysAgo = 180; $daysAgo >= 1; $daysAgo--) {
    // Tendencia creciente (de ~0,5 a ~3,5 pedidos al día) con más ventas el fin de semana.
    $weekday = (int) date('N', strtotime("-$daysAgo days"));
    $expected = 0.5 + 3.0 * (180 - $daysAgo) / 180 + ($weekday >= 6 ? 0.6 : 0.0);
    $orders = (int) floor($expected) + (mt_rand() / mt_getrandmax() < $expected - floor($expected) ? 1 : 0);

    for ($n = 0; $n < $orders; $n++) {
        $lines = [];
        $subtotal = 0;
        foreach ((array) array_rand($products, mt_rand(1, 3)) as $index) {
            $p = $products[$index];
            $qty = mt_rand(1, 10) <= 8 ? 1 : 2;
            $unit = to_cents((string) $p['price']);
            $subtotal += $unit * $qty;
            $lines[] = [$p, $qty, $unit];
        }
        $shipping = ($freeFrom > 0 && $subtotal >= $freeFrom) ? 0 : $flat;
        $roll = mt_rand(1, 100);
        $status = $roll <= 5 ? 'cancelled' : ($daysAgo > 10 ? 'delivered' : ['paid', 'preparing', 'shipped', 'received'][mt_rand(0, 3)]);
        $name = $names[mt_rand(0, count($names) - 1)];
        $minutes = $daysAgo * 1440 - mt_rand(0, 1439);
        $when = "NOW() - INTERVAL $minutes MINUTE";
        $address = json_encode(['line1' => 'Dirección de prueba ' . mt_rand(1, 999), 'city' => $cities[mt_rand(0, count($cities) - 1)],
            'state' => '', 'postal_code' => '', 'country' => 'VE'], JSON_UNESCAPED_UNICODE);

        $sql .= sprintf(
            "INSERT INTO orders (user_id, status, subtotal, shipping_cost, total, currency, customer_name, customer_email, customer_phone, shipping_address, payment_method, checkout_token, created_at, updated_at) VALUES (@demo_user, '%s', %s, %s, %s, '%s', %s, '%s', '0412-0000000', %s, '%s', '%s', %s, %s);\nSET @o = LAST_INSERT_ID();\n",
            $status, cents_to_decimal($subtotal), cents_to_decimal($shipping), cents_to_decimal($subtotal + $shipping), $currency,
            $q($name), DEMO_EMAIL, $q((string) $address), mt_rand(0, 1) ? 'bank_transfer' : 'cash_on_delivery',
            'demo' . substr(hash('sha256', $daysAgo . '-' . $n), 0, 60), $when, $when
        );
        foreach ($lines as [$p, $qty, $unit]) {
            $sql .= sprintf(
                "INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, unit_price, subtotal) VALUES (@o, (SELECT id FROM products WHERE sku = %s), %s, %s, %d, %s, %s);\n",
                $q((string) $p['sku']), $q((string) $p['name']), $q((string) $p['sku']), $qty, cents_to_decimal($unit), cents_to_decimal($unit * $qty)
            );
        }
        $count++;
    }
}

$file = BASE_PATH . '/database/sales_demo.sql';
file_put_contents($file, $sql);
echo "$count pedidos de demostración escritos en database/sales_demo.sql\n";

if (isset($options['apply'])) {
    $run($sql);
    echo "Aplicados en la base local.\n";
}
