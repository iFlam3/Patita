<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Adaptador para https://www.petstore.direct (tienda Shopify).
 *
 * Verificación realizada el 2026-10-07 sobre UNA ficha pública de /products/
 * (ruta permitida por robots.txt para "User-agent: *"): la página incluye un
 * bloque JSON-LD con "@type": "Product" y los campos name, brand.name,
 * description, sku, image (cadena) y offers (price, priceCurrency).
 * No se verificaron selectores HTML, por eso el adaptador usa solo JSON-LD.
 *
 * Términos de servicio revisados el mismo día: prohíben copiar, reproducir o
 * republicar contenido con fines públicos o comerciales sin consentimiento
 * previo y por escrito de PetStore.Direct LLC. Por eso la fuente está
 * deshabilitada por defecto (IMPORT_ALLOWED_SOURCES vacío).
 */
final class PetstoreDirectAdapter implements SourceAdapter
{
    public function key(): string
    {
        return 'petstore_direct';
    }

    public function name(): string
    {
        return 'PetStore.Direct';
    }

    public function host(): string
    {
        return 'www.petstore.direct';
    }

    public function verification(): array
    {
        return [
            'status'     => 'verified',
            'checked_at' => '2026-10-07',
            'note'       => 'Se confirmó en una ficha de producto pública un bloque JSON-LD Product con name, brand, description, sku, image y offers. Solo se usa JSON-LD; no hay selectores HTML. La estructura puede cambiar sin aviso: vuelve a verificar antes de cada uso.',
        ];
    }

    public function termsNote(): string
    {
        return 'robots.txt (revisado 2026-10-07) permite /products/ para User-agent: * y bloquea carrito, cuenta, búsqueda, colecciones ordenadas/filtradas y /policies/. '
            . 'Los términos de servicio prohíben copiar o republicar contenido con fines comerciales sin consentimiento previo por escrito. '
            . 'Habilítala solo con esa autorización.';
    }

    public function supports(string $url): bool
    {
        $parts = parse_url($url);

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === $this->host()
            && preg_match('#^/products/[a-z0-9][a-z0-9-]*/?$#i', (string) ($parts['path'] ?? '')) === 1;
    }

    public function extract(string $html, string $url): array
    {
        $data = JsonLdProductExtractor::extract($html);
        if ($data === null) {
            throw new ImportException('La página no contiene JSON-LD de tipo Product. No se usan selectores HTML no verificados; revisa la URL o vuelve a verificar el adaptador.');
        }

        return $data;
    }
}
