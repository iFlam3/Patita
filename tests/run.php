<?php

declare(strict_types=1);

/*
 * Pruebas unitarias sin dependencias (no requieren base de datos).
 * Uso: php tests/run.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Validator;
use App\Services\Cart;
use App\Services\PetTools;
use App\Services\SalesForecast;

$passed = 0;
$failed = [];

$check = static function (string $name, bool $condition) use (&$passed, &$failed): void {
    if ($condition) {
        $passed++;
    } else {
        $failed[] = $name;
    }
};

// ------------------------------------------------------------ dinero

$check('dinero: 549.00 a centavos', to_cents('549.00') === 54900);
$check('dinero: 0.5 a centavos', to_cents('0.5') === 50);
$check('dinero: redondeo del tercer decimal', to_cents('12.345') === 1235 && to_cents('12.344') === 1234);
$check('dinero: entero', to_cents(7) === 700);
$check('dinero: centavos a decimal', cents_to_decimal(54900) === '549.00' && cents_to_decimal(5) === '0.05' && cents_to_decimal(-150) === '-1.50');
$check('dinero: 0.1 + 0.2 sin error de coma flotante', cents_to_decimal(to_cents('0.10') + to_cents('0.20')) === '0.30');
$threw = false;
try {
    to_cents('12,50');
} catch (InvalidArgumentException $e) {
    $threw = true;
}
$check('dinero: rechaza coma decimal', $threw);
$check('dinero: formato de respaldo incluye la moneda', str_contains(format_money(54900, 'MXN'), '549.00'));

// ------------------------------------------------------------ texto y catálogo

$check('slug: acentos y signos', slugify('Arnés acolchado, talla S') === 'arnes-acolchado-talla-s');
$check('slug: punto final', slugify('Madriguera Co.') === 'madriguera-co');
$check('slug: medidas con ×', slugify('Terrario 60 × 30') === 'terrario-60-x-30');
$check('catálogo: 7 mascotas', count(pets()) === 7);
$check('catálogo: 9 categorías', count(categories()) === 9);
$check('catálogo: slug de mascota', pet_by_slug('animales-pequenos') === 'small_pet' && pet_by_slug('dinosaurios') === null);
$check('pedido: número con ceros', order_number(42) === 'PAT-000042');
$check('ruta segura: rechaza URL externa', safe_path('//evil.example') === '/' && safe_path('https://x.test') === '/' && safe_path('/cuenta') === '/cuenta');
$check('escape HTML', e('<script>"x"</script>') === '&lt;script&gt;&quot;x&quot;&lt;/script&gt;');

// ------------------------------------------------------------ validación

$v = new Validator(['email' => 'no-es-correo', 'phone' => '+52 (55) 1234-5678', 'price' => '10.999', 'name' => 'Al']);
$v->email('email')->phone('phone')->decimal('price', 'El precio', 0, 100)->length('name', 'El nombre', 3, 10);
$errors = $v->errors();
$check('validación: correo inválido', isset($errors['email']));
$check('validación: teléfono flexible válido', !isset($errors['phone']));
$check('validación: más de dos decimales', isset($errors['price']));
$check('validación: longitud mínima', isset($errors['name']));
$v2 = new Validator(['phone' => '12']);
$check('validación: teléfono demasiado corto', isset($v2->phone('phone')->errors()['phone']));

// ------------------------------------------------------------ carrito

$check('carrito: huella independiente del orden', Cart::fingerprint([[2, 1, 500], [1, 3, 100]], 0) === Cart::fingerprint([[1, 3, 100], [2, 1, 500]], 0));
$check('carrito: huella cambia con el precio', Cart::fingerprint([[1, 1, 100]], 0) !== Cart::fingerprint([[1, 1, 101]], 0));
$check('carrito: huella cambia con el envío', Cart::fingerprint([[1, 1, 100]], 0) !== Cart::fingerprint([[1, 1, 100]], 9900));

// ------------------------------------------------------------ herramientas

$check('edad: gato de 1 año = 15', PetTools::humanAge('gato', 1) === 15.0);
$check('edad: gato de 2 años = 24', PetTools::humanAge('gato', 2) === 24.0);
$check('edad: gato de 10 años = 56', PetTools::humanAge('gato', 10) === 56.0);
$check('edad: perro gigante envejece más rápido', PetTools::humanAge('perro', 10, 'gigante') > PetTools::humanAge('perro', 10, 'pequeno'));
$check('edad: perro mediano de 6 meses = 7.5', PetTools::humanAge('perro', 0.5) === 7.5);
$tox = PetTools::toxicity('dog', 'chocolate');
$check('toxicidad: chocolate tóxico para perros', $tox !== null && $tox['level'] === 'toxic');
$check('toxicidad: aguacate tóxico para aves', (PetTools::toxicity('bird', 'aguacate')['level'] ?? '') === 'toxic');
$check('toxicidad: sin datos si no hay fuente', (PetTools::toxicity('fish', 'macadamia')['level'] ?? '') === 'nodata');
$check('toxicidad: alimento desconocido', PetTools::toxicity('dog', 'plutonio') === null);
$w = PetTools::healthyWeight(22, 7);
$check('peso: condición 7 = 20 % de exceso', $w['status'] === 'over' && $w['excess_percent'] === 20 && $w['ideal'] === 18.3);
$check('peso: condición ideal conserva el peso', PetTools::healthyWeight(10, 5)['ideal'] === 10.0);
$check('peso: delgadez no calcula cifra', PetTools::healthyWeight(10, 3)['ideal'] === null);
$check('países: lista completa', count((array) config('catalog.countries')) > 200 && country_name('US') === 'Estados Unidos');

$noKey = false;
try {
    (new App\Services\GeminiClient('', 'x'))->reply('s', [['role' => 'user', 'text' => 'hola']]);
} catch (RuntimeException $e) {
    $noKey = true;
}
$check('chat: sin GEMINI_API_KEY no llama a la API', $noKey);

$line = SalesForecast::fit([1.0, 3.0, 5.0, 7.0]);
$check('regresión: recta exacta y = 1 + 2x', abs($line['a'] - 1) < 1e-9 && abs($line['b'] - 2) < 1e-9 && abs((float) $line['r2'] - 1) < 1e-9);
$check('regresión: proyecta los siguientes periodos', SalesForecast::project($line, 4, 2) === [9.0, 11.0]);
$noisy = SalesForecast::fit([2.0, 4.0, 3.0, 6.0, 5.0]);
$check('regresión: pendiente y R² con ruido', abs($noisy['b'] - 0.8) < 1e-9 && abs((float) $noisy['r2'] - 0.64) < 1e-9);
$check('regresión: proyección no baja de cero', SalesForecast::project(SalesForecast::fit([9.0, 5.0, 1.0]), 3, 2) === [0.0, 0.0]);
$check('regresión: serie constante sin R²', SalesForecast::fit([4.0, 4.0, 4.0])['r2'] === null);

// ------------------------------------------------------------ resultado

echo sprintf("%d pruebas correctas, %d fallidas.\n", $passed, count($failed));
foreach ($failed as $name) {
    echo "  FALLÓ: $name\n";
}
exit($failed === [] ? 0 : 1);
