<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Adaptador para https://www.petsmart.com — NO VERIFICADO.
 *
 * No se inspeccionó ninguna ficha de producto: los Términos de uso de PetSmart
 * (revisados el 2026-10-07) prohíben acceder al sitio para construir un
 * servicio similar o competitivo o para descargar, copiar o recopilar su
 * contenido, y prohíben su explotación comercial. Su robots.txt además bloquea
 * toda URL con parámetros ("Disallow: /*?"), /search, /cart y /checkout.
 *
 * Por eso este adaptador:
 *  - está deshabilitado por defecto y requiere autorización escrita para usarse;
 *  - no contiene selectores HTML (no se inventan sin verificar);
 *  - solo intenta leer JSON-LD Product genérico y falla con un error claro si
 *    no existe. Que funcione NO está comprobado.
 */
final class PetsmartAdapter implements SourceAdapter
{
    public function key(): string
    {
        return 'petsmart';
    }

    public function name(): string
    {
        return 'PetSmart';
    }

    public function host(): string
    {
        return 'www.petsmart.com';
    }

    public function verification(): array
    {
        return [
            'status'     => 'unverified',
            'checked_at' => null,
            'note'       => 'No verificado. No se inspeccionaron páginas porque los términos de uso lo prohíben para este propósito. Solo intenta leer JSON-LD Product genérico; no hay selectores HTML.',
        ];
    }

    public function termsNote(): string
    {
        return 'Los términos de uso (revisados 2026-10-07) prohíben acceder al sitio para construir un servicio similar o competitivo, descargar, copiar o recopilar contenido, y explotarlo comercialmente. '
            . 'robots.txt bloquea URL con parámetros, /search, /cart y /checkout. No la habilites sin autorización escrita de PetSmart.';
    }

    /**
     * Solo exige https, el dominio y que no haya parámetros (robots.txt los
     * bloquea). No se valida el formato de la ruta porque no se verificó.
     */
    public function supports(string $url): bool
    {
        $parts = parse_url($url);

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === $this->host()
            && !isset($parts['query'])
            && (string) ($parts['path'] ?? '') !== '';
    }

    public function extract(string $html, string $url): array
    {
        $data = JsonLdProductExtractor::extract($html);
        if ($data === null) {
            throw new ImportException('No se encontró JSON-LD Product. Este adaptador no está verificado y no tiene selectores HTML de respaldo.');
        }

        return $data;
    }
}
