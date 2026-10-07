<?php

declare(strict_types=1);

/*
 * Pruebas unitarias sin dependencias (no requieren base de datos).
 * Uso: php tests/run.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Validator;
use App\Services\Cart;
use App\Services\Import\Classifier;
use App\Services\Import\FileImportReader;
use App\Services\Import\HttpClient;
use App\Services\Import\JsonLdProductExtractor;
use App\Services\Import\RobotsTxt;

$passed = 0;
$failed = [];

$check = static function (string $name, bool $condition) use (&$passed, &$failed): void {
    if ($condition) {
        $passed++;
    } else {
        $failed[] = $name;
    }
};

// ------------------------------------------------------------ robots.txt

$robots = RobotsTxt::parse(<<<'TXT'
# Comentario
User-agent: *
Disallow: /cart
Disallow: /collections/*sort_by*
Disallow: /*?
Allow: /products/
Disallow: /search$
Disallow:

User-agent: PatitaCatalogBot
Disallow: /privado/
Crawl-delay: 5

User-agent: otro
User-agent: tercero
Disallow: /
TXT);

$browser = 'Mozilla/5.0';
$bot = 'PatitaCatalogBot/1.0 (+https://example.test/bot)';
$check('robots: permite /products/', $robots->isAllowed($browser, '/products/collar'));
$check('robots: bloquea /cart', !$robots->isAllowed($browser, '/cart'));
$check('robots: bloquea prefijo /cart/x', !$robots->isAllowed($browser, '/cart/x'));
$check('robots: comodín sort_by', !$robots->isAllowed($browser, '/collections/perros?sort_by=price'));
$check('robots: gana la regla más larga (Allow /products/ sobre /*?)', $robots->isAllowed($browser, '/products/x?variant=1'));
$check('robots: /*? bloquea otras rutas con parámetros', !$robots->isAllowed($browser, '/pages/x?a=1'));
$check('robots: ancla $ exacta', !$robots->isAllowed($browser, '/search'));
$check('robots: ancla $ no bloquea subrutas', $robots->isAllowed($browser, '/search/mas'));
$check('robots: grupo específico reemplaza a *', $robots->isAllowed($bot, '/cart'));
$check('robots: grupo específico aplica sus reglas', !$robots->isAllowed($bot, '/privado/a'));
$check('robots: crawl-delay del grupo específico', $robots->crawlDelay($bot) === 5.0);
$check('robots: varios user-agent en un grupo', !$robots->isAllowed('tercero/2.0', '/'));
$check('robots: robots.txt siempre permitido', $robots->isAllowed('tercero/2.0', '/robots.txt'));
$check('robots: archivo vacío permite todo', RobotsTxt::parse('')->isAllowed($bot, '/lo-que-sea'));

// ------------------------------------------------------------ JSON-LD

$html = (string) file_get_contents(__DIR__ . '/fixtures/product-jsonld.html');
$data = JsonLdProductExtractor::extract($html);
$check('jsonld: encuentra Product tras bloques inválidos', $data !== null);
$check('jsonld: nombre sin HTML ni entidades', ($data['name'] ?? '') === 'Comedero & bebedero doble');
$check('jsonld: marca desde objeto Brand', ($data['brand'] ?? '') === 'Marca de Prueba');
$check('jsonld: descripción normalizada', ($data['description'] ?? '') === 'Base antideslizante. Dos platos.');
$check('jsonld: sku numérico a texto', ($data['sku'] ?? '') === '12345');
$check('jsonld: imagen con protocolo relativo', ($data['image'] ?? '') === 'https://cdn.example.test/img/comedero.jpg');
$check('jsonld: precio normalizado', ($data['price'] ?? '') === '19.99');
$check('jsonld: moneda en mayúsculas', ($data['currency'] ?? '') === 'USD');

$graph = '<script type="application/ld+json">{"@graph":[{"@type":"WebPage"},{"@type":["Product"],"name":"Hamaca","brand":"Marca Texto","offers":[{"@type":"AggregateOffer","lowPrice":7,"priceCurrency":"MXN"}],"image":{"url":"https://example.test/h.png"}}]}</script>';
$g = JsonLdProductExtractor::extract($graph);
$check('jsonld: @graph y @type en arreglo', ($g['name'] ?? '') === 'Hamaca');
$check('jsonld: marca como texto', ($g['brand'] ?? '') === 'Marca Texto');
$check('jsonld: AggregateOffer lowPrice', ($g['price'] ?? '') === '7.00');
$check('jsonld: imagen como objeto', ($g['image'] ?? '') === 'https://example.test/h.png');

$group = '<script type="application/ld+json">{"@type":"ProductGroup","name":"Correa","hasVariant":[{"@type":"Product","offers":{"price":"10.5","priceCurrency":"EUR"}}]}</script>';
$pg = JsonLdProductExtractor::extract($group);
$check('jsonld: ProductGroup usa la oferta de la primera variante', ($pg['price'] ?? '') === '10.50' && ($pg['currency'] ?? '') === 'EUR');
$check('jsonld: sin datos estructurados devuelve null', JsonLdProductExtractor::extract('<html><body>Hola</body></html>') === null);
$check('jsonld: Product sin nombre se descarta', JsonLdProductExtractor::extract('<script type="application/ld+json">{"@type":"Product"}</script>') === null);

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

// ------------------------------------------------------------ importador

$check('clasificador: conejo', Classifier::pet('Heno para conejos') === 'small_pet');
$check('clasificador: inglés', Classifier::pet('Rabbit hay feeder') === 'small_pet');
$check('clasificador: no adivina', Classifier::pet('Producto genérico') === null);
$check('clasificador: categoría', Classifier::category('Dog Feeding Mats') === 'bowls_feeders');
$check('clasificador: no confunde "cat" dentro de otra palabra', Classifier::pet('Catalogue stand') === null);

$tmp = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($tmp, "\xEF\xBB\xBFsku;name;price\nA-1;\"Uno; con punto y coma\";10.50\n\nA-2;Dos\n");
$csvPath = $tmp . '.csv';
rename($tmp, $csvPath);
$rows = FileImportReader::read($csvPath);
unlink($csvPath);
$check('csv: detecta ; y quita BOM', isset($rows[0]['sku']) && $rows[0]['sku'] === 'A-1');
$check('csv: respeta comillas', ($rows[0]['name'] ?? '') === 'Uno; con punto y coma');
$check('csv: ignora líneas vacías y completa columnas', count($rows) === 2 && ($rows[1]['price'] ?? null) === '');

$check('bloqueo: 403 con captcha', HttpClient::looksBlocked(403, '<div class="g-recaptcha">captcha</div>'));
$check('bloqueo: 429 sin marcadores no es desafío', !HttpClient::looksBlocked(429, 'Too many requests'));
$check('bloqueo: 200 normal', !HttpClient::looksBlocked(200, '<script type="application/ld+json">{}</script>'));
$check('bloqueo: 200 con desafío', HttpClient::looksBlocked(200, '<p>Please verify you are human (captcha)</p>'));
$bigPage = '<script src="https://www.google.com/recaptcha/api.js"></script><div class="captcha"></div>'
    . str_repeat('<p>contenido</p>', 3000) . '<script type="application/ld+json">{"@type":"Product"}</script>';
$check('bloqueo: ficha grande con reCAPTCHA de formulario no es bloqueo', !HttpClient::looksBlocked(200, $bigPage));

// ------------------------------------------------------------ carrito

$check('carrito: huella independiente del orden', Cart::fingerprint([[2, 1, 500], [1, 3, 100]], 0) === Cart::fingerprint([[1, 3, 100], [2, 1, 500]], 0));
$check('carrito: huella cambia con el precio', Cart::fingerprint([[1, 1, 100]], 0) !== Cart::fingerprint([[1, 1, 101]], 0));
$check('carrito: huella cambia con el envío', Cart::fingerprint([[1, 1, 100]], 0) !== Cart::fingerprint([[1, 1, 100]], 9900));

// ------------------------------------------------------------ resultado

echo sprintf("%d pruebas correctas, %d fallidas.\n", $passed, count($failed));
foreach ($failed as $name) {
    echo "  FALLÓ: $name\n";
}
exit($failed === [] ? 0 : 1);
