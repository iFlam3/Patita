<?php

declare(strict_types=1);

namespace App\Services\Import;

use DOMDocument;
use DOMXPath;

/**
 * Lee datos estructurados schema.org Product (o ProductGroup) en bloques
 * <script type="application/ld+json">. Es la vía preferida de extracción:
 * no depende del diseño visual de la página.
 */
final class JsonLdProductExtractor
{
    /**
     * @return array{name: string, brand: ?string, description: ?string, sku: ?string, image: ?string, price: ?string, currency: ?string, url: ?string}|null
     */
    public static function extract(string $html): ?array
    {
        if (trim($html) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $scripts = (new DOMXPath($dom))->query('//script[@type="application/ld+json"]');
        if ($scripts === false) {
            return null;
        }

        foreach ($scripts as $script) {
            $json = json_decode(trim((string) $script->textContent), true);
            if (!is_array($json)) {
                continue;
            }
            $node = self::findProduct($json);
            if ($node !== null) {
                return self::map($node);
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $json
     * @return array<string, mixed>|null
     */
    private static function findProduct(array $json): ?array
    {
        $candidates = [];
        if (self::isList($json)) {
            $candidates = $json;
        } elseif (isset($json['@graph']) && is_array($json['@graph'])) {
            $candidates = $json['@graph'];
        } else {
            $candidates = [$json];
        }

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $types = (array) ($candidate['@type'] ?? []);
            if (in_array('Product', $types, true) || in_array('ProductGroup', $types, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     * @return array{name: string, brand: ?string, description: ?string, sku: ?string, image: ?string, price: ?string, currency: ?string, url: ?string}|null
     */
    private static function map(array $node): ?array
    {
        $name = self::text($node['name'] ?? null);
        if ($name === null) {
            return null;
        }

        $brand = $node['brand'] ?? null;
        if (is_array($brand)) {
            $brand = $brand['name'] ?? (self::isList($brand) && is_array($brand[0] ?? null) ? ($brand[0]['name'] ?? null) : null);
        }

        $offers = $node['offers'] ?? null;
        if ($offers === null && isset($node['hasVariant']) && is_array($node['hasVariant'])) {
            $first = $node['hasVariant'][0] ?? null;
            $offers = is_array($first) ? ($first['offers'] ?? null) : null;
        }
        [$price, $currency] = self::offer($offers);

        $sku = $node['sku'] ?? ($node['mpn'] ?? null);

        return [
            'name'        => mb_substr($name, 0, 200, 'UTF-8'),
            'brand'       => self::text($brand) !== null ? mb_substr((string) self::text($brand), 0, 120, 'UTF-8') : null,
            'description' => self::text($node['description'] ?? null),
            'sku'         => is_scalar($sku) ? mb_substr(trim((string) $sku), 0, 64, 'UTF-8') : null,
            'image'       => self::image($node['image'] ?? null),
            'price'       => $price,
            'currency'    => $currency,
            'url'         => is_string($node['url'] ?? null) ? $node['url'] : null,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private static function offer(mixed $offers): array
    {
        if (!is_array($offers)) {
            return [null, null];
        }
        if (self::isList($offers)) {
            $offers = is_array($offers[0] ?? null) ? $offers[0] : [];
        }

        $price = $offers['price'] ?? ($offers['lowPrice'] ?? null);
        if ($price === null && isset($offers['priceSpecification']) && is_array($offers['priceSpecification'])) {
            $price = $offers['priceSpecification']['price'] ?? null;
        }
        $currency = $offers['priceCurrency'] ?? ($offers['priceSpecification']['priceCurrency'] ?? null);

        $priceText = is_scalar($price) ? trim((string) $price) : '';
        if ($priceText === '' || preg_match('/^\d+(\.\d+)?$/', $priceText) !== 1) {
            $priceText = null;
        } else {
            $priceText = cents_to_decimal(to_cents($priceText));
        }

        $currencyText = is_string($currency) && preg_match('/^[A-Za-z]{3}$/', trim($currency)) === 1 ? strtoupper(trim($currency)) : null;

        return [$priceText, $currencyText];
    }

    private static function image(mixed $image): ?string
    {
        if (is_array($image)) {
            $image = self::isList($image) ? ($image[0] ?? null) : ($image['url'] ?? ($image['contentUrl'] ?? null));
            if (is_array($image)) {
                $image = $image['url'] ?? ($image['contentUrl'] ?? null);
            }
        }
        if (!is_string($image) || $image === '') {
            return null;
        }
        if (str_starts_with($image, '//')) {
            $image = 'https:' . $image;
        }

        return filter_var($image, FILTER_VALIDATE_URL) !== false ? $image : null;
    }

    private static function text(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : $text;
    }

    /**
     * @param array<mixed> $array
     */
    private static function isList(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }
}
