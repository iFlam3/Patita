<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;
use App\Services\Auth;

/*
 * Funciones globales de apoyo para vistas y controladores.
 */

// ------------------------------------------------------------ configuración

/**
 * @return array<string, mixed>
 */
function &config_store(): array
{
    static $store = null;
    if ($store === null) {
        $store = [
            'app'     => require BASE_PATH . '/app/config/app.php',
            'catalog' => require BASE_PATH . '/app/config/catalog.php',
            'runtime' => ['base_path' => '', 'https' => false],
        ];
    }

    return $store;
}

/**
 * Lee configuración con notación de puntos: config('app.currency').
 */
function config(string $key, mixed $default = null): mixed
{
    $value = config_store();
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function config_set(string $key, mixed $value): void
{
    $store = &config_store();
    $segments = explode('.', $key);
    $last = array_pop($segments);
    $node = &$store;
    foreach ($segments as $segment) {
        if (!isset($node[$segment]) || !is_array($node[$segment])) {
            $node[$segment] = [];
        }
        $node = &$node[$segment];
    }
    $node[$last] = $value;
}

// ------------------------------------------------------------ escape y URLs

/**
 * Escapa para HTML (texto y atributos). Úsala en toda salida dinámica.
 */
function e(mixed $value): string
{
    if ($value === null || is_array($value) || is_object($value)) {
        return '';
    }
    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * URL interna respetando el subdirectorio de instalación.
 *
 * @param array<string, mixed> $query
 */
function url(string $path = '/', array $query = []): string
{
    $base = (string) config('runtime.base_path', '');
    $query = array_filter($query, static fn ($v): bool => $v !== null && $v !== '' && $v !== false);
    $qs = $query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '';

    return $base . '/' . ltrim($path, '/') . $qs;
}

/**
 * URL absoluta (para correos) basada en APP_URL.
 *
 * @param array<string, mixed> $query
 */
function absolute_url(string $path, array $query = []): string
{
    $qs = $query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '';

    return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/') . $qs;
}

/**
 * Archivo de public/ con sello de versión para invalidar caché.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/' . $path;
    $version = is_file($file) ? '?v=' . (string) filemtime($file) : '';

    return url('/' . $path) . $version;
}

/**
 * Fuente de la imagen de un producto: URL https externa, ruta local de
 * public/ o la ilustración de demostración según mascota y categoría.
 */
function product_image(?string $imageUrl, string $petType = '', string $category = ''): string
{
    $imageUrl = trim((string) $imageUrl);
    if ($imageUrl !== '' && preg_match('#^https://#i', $imageUrl) === 1) {
        return $imageUrl;
    }
    if ($imageUrl !== '' && preg_match('#^[a-zA-Z0-9/_.-]+$#', $imageUrl) === 1 && !str_contains($imageUrl, '..')
        && is_file(BASE_PATH . '/public/' . ltrim($imageUrl, '/'))) {
        return asset($imageUrl);
    }

    $fallback = 'assets/images/demo/' . $petType . '-' . $category . '.svg';
    if (preg_match('#^[a-z_]+-[a-z_]+$#', $petType . '-' . $category) === 1 && is_file(BASE_PATH . '/public/' . $fallback)) {
        return asset($fallback);
    }

    return asset('assets/images/demo/placeholder.svg');
}

function icon(string $name, string $class = 'icon'): string
{
    static $sprite = null;
    if ($sprite === null) {
        $sprite = asset('assets/images/icons.svg');
    }

    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="'
        . e($sprite) . '#i-' . e($name) . '"></use></svg>';
}

/**
 * Solo permite redirigir a rutas internas (evita redirecciones abiertas).
 */
function safe_path(?string $path, string $fallback = '/'): string
{
    if (!is_string($path) || $path === '' || $path[0] !== '/' || str_starts_with($path, '//')
        || str_contains($path, '\\') || preg_match('/[\x00-\x1F]/', $path) === 1) {
        return $fallback;
    }

    return $path;
}

// ------------------------------------------------------------ formularios

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function old(string $key, string $default = ''): string
{
    return Session::old($key, $default);
}

/**
 * Atributos ARIA y mensaje de error accesible para un campo.
 *
 * @param array<string, string> $errors
 */
function field_attrs(string $field, array $errors, string $hintId = ''): string
{
    $describedBy = array_filter([$hintId, isset($errors[$field]) ? 'error-' . $field : '']);
    $attrs = $describedBy !== [] ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '';

    return isset($errors[$field]) ? ' aria-invalid="true"' . $attrs : $attrs;
}

/**
 * @param array<string, string> $errors
 */
function field_error(string $field, array $errors): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p class="field-error" id="error-' . e($field) . '">' . icon('circle-alert', 'icon icon-sm')
        . '<span>' . e($errors[$field]) . '</span></p>';
}

// ------------------------------------------------------------ dinero

/**
 * Convierte un importe decimal ("549.00") a centavos enteros sin pasar por float.
 */
function to_cents(string|int|float $amount): int
{
    if (is_int($amount)) {
        return $amount * 100;
    }
    $text = is_float($amount) ? number_format($amount, 2, '.', '') : trim($amount);
    if (preg_match('/^(-)?(\d+)(?:\.(\d+))?$/', $text, $m) !== 1) {
        throw new InvalidArgumentException('Importe inválido: ' . $text);
    }
    // Dos decimales; si llegan más, se redondea con el tercero (mitad hacia arriba).
    $digits = str_pad($m[3] ?? '', 3, '0');
    $cents = ((int) $m[2]) * 100 + (int) substr($digits, 0, 2) + ((int) $digits[2] >= 5 ? 1 : 0);

    return $m[1] === '-' ? -$cents : $cents;
}

function cents_to_decimal(int $cents): string
{
    $sign = $cents < 0 ? '-' : '';
    $abs = abs($cents);

    return $sign . intdiv($abs, 100) . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
}

/**
 * Formato de respaldo en el servidor (correos y navegadores sin JavaScript).
 * En la interfaz, assets/js/app.js reformatea con Intl.NumberFormat.
 */
function format_money(int $cents, ?string $currency = null): string
{
    $currency = $currency ?? (string) config('app.currency');
    $decimals = (int) config('catalog.currency_decimals.' . $currency, 2);

    if (class_exists('NumberFormatter')) {
        $formatter = new NumberFormatter(str_replace('-', '_', (string) config('app.locale')), NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($cents / 100, $currency);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    $symbols = ['MXN' => '$', 'COP' => '$', 'CLP' => '$', 'ARS' => '$', 'UYU' => '$', 'USD' => 'US$',
        'PEN' => 'S/', 'BRL' => 'R$', 'GTQ' => 'Q', 'CRC' => '₡', 'DOP' => 'RD$'];
    $number = number_format($cents / 100, $decimals, '.', ',');

    return isset($symbols[$currency]) ? $symbols[$currency] . $number . ' ' . $currency : $currency . ' ' . $number;
}

/**
 * Precio accesible: <data value="549.00"> que el JS formatea con Intl.
 */
function price_tag(int $cents, ?string $currency = null, string $class = 'price'): string
{
    $currency = $currency ?? (string) config('app.currency');

    return '<data class="' . e($class) . '" value="' . e(cents_to_decimal($cents)) . '" data-currency="'
        . e($currency) . '">' . e(format_money($cents, $currency)) . '</data>';
}

// ------------------------------------------------------------ fechas y texto

function format_datetime(?string $utc, string $format = 'd/m/Y H:i'): string
{
    if ($utc === null || $utc === '') {
        return '';
    }
    $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));

    return $date->setTimezone(new DateTimeZone((string) config('app.timezone', 'UTC')))->format($format);
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c', '×' => 'x', '&' => 'y',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

    return trim($text, '-');
}

function str_limit(string $text, int $max): string
{
    return mb_strlen($text, 'UTF-8') > $max ? rtrim(mb_substr($text, 0, $max - 1, 'UTF-8')) . '…' : $text;
}

// ------------------------------------------------------------ catálogo

/**
 * @return array<string, array<string, mixed>>
 */
function pets(): array
{
    return (array) config('catalog.pets', []);
}

function pet_label(string $code): string
{
    return (string) (pets()[$code]['label'] ?? 'Sin mascota');
}

function pet_slug(string $code): string
{
    return (string) (pets()[$code]['slug'] ?? '');
}

function pet_by_slug(string $slug): ?string
{
    foreach (pets() as $code => $pet) {
        if ($pet['slug'] === $slug) {
            return $code;
        }
    }

    return null;
}

/**
 * @return array<string, array{label: string, slug: string, icon: string}>
 */
function categories(): array
{
    return (array) config('catalog.categories', []);
}

function category_label(string $code): string
{
    return (string) (categories()[$code]['label'] ?? 'Sin categoría');
}

function category_slug(string $code): string
{
    return (string) (categories()[$code]['slug'] ?? '');
}

function category_by_slug(string $slug): ?string
{
    foreach (categories() as $code => $category) {
        if ($category['slug'] === $slug) {
            return $code;
        }
    }

    return null;
}

function order_status_label(string $status): string
{
    return (string) config('catalog.order_statuses.' . $status . '.label', $status);
}

function payment_method_label(string $method): string
{
    return (string) config('catalog.payment_methods.' . $method . '.label', $method);
}

/**
 * Métodos de pago habilitados en .env que además existen en el catálogo.
 *
 * @return array<string, array{label: string, description: string}>
 */
function enabled_payment_methods(): array
{
    $defined = (array) config('catalog.payment_methods', []);
    $enabled = [];
    foreach ((array) config('app.shop.payment_methods', []) as $code) {
        if (isset($defined[$code])) {
            $enabled[$code] = $defined[$code];
        }
    }

    return $enabled;
}

function country_name(string $code): string
{
    return (string) config('catalog.countries.' . strtoupper($code), strtoupper($code));
}

function order_number(int $id): string
{
    return 'PAT-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

// ------------------------------------------------------------ autenticación

/**
 * @return array<string, mixed>|null
 */
function auth_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

// ------------------------------------------------------------ registro

/**
 * Agrega una línea al registro storage/logs/{canal}.log.
 *
 * @param array<string, mixed> $context
 */
function log_message(string $channel, string $message, array $context = []): void
{
    $channel = preg_replace('/[^a-z0-9_-]/', '', strtolower($channel)) ?: 'app';
    $line = '[' . gmdate('Y-m-d\TH:i:s\Z') . '] ' . str_replace(["\r", "\n"], ' ', $message);
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    @file_put_contents(BASE_PATH . '/storage/logs/' . $channel . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}
