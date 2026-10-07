<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Contrato de un adaptador por sitio. Cada fuente aísla su propia lógica de
 * extracción y declara si fue verificada y bajo qué condiciones de uso.
 */
interface SourceAdapter
{
    /** Clave usada en la CLI, en IMPORT_ALLOWED_SOURCES y en products.source_site. */
    public function key(): string;

    public function name(): string;

    /** Único dominio permitido para las URL de esta fuente. */
    public function host(): string;

    /**
     * Estado de verificación del adaptador.
     *
     * @return array{status: string, checked_at: ?string, note: string} status = verified | unverified
     */
    public function verification(): array;

    /** Resumen de robots.txt y términos de uso revisados. */
    public function termsNote(): string;

    /** Comprueba que la URL corresponde a una ficha de producto de la fuente. */
    public function supports(string $url): bool;

    /**
     * Extrae los datos de una ficha de producto.
     *
     * @return array{name: string, brand: ?string, description: ?string, sku: ?string, image: ?string, price: ?string, currency: ?string, url: ?string}
     * @throws ImportException si la página no contiene datos utilizables.
     */
    public function extract(string $html, string $url): array;
}
