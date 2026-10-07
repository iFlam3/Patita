<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Core\Database;
use App\Models\Product;

/**
 * Convierte registros importados en productos.
 *
 * Reglas comunes:
 *  - Nunca se convierte el precio externo en precio local: source_price y
 *    source_currency se guardan aparte.
 *  - El stock externo no se usa: un producto extraído entra con stock 0.
 *  - Los productos extraídos quedan INACTIVOS (precio 0) hasta que un
 *    administrador defina precio local, stock, texto e imagen propios.
 */
final class ProductImporter
{
    public function __construct(private bool $dryRun)
    {
    }

    /**
     * @param array{name: string, brand: ?string, description: ?string, sku: ?string, image: ?string, price: ?string, currency: ?string, url: ?string} $data
     * @return array{action: string, message: string}
     */
    public function importScraped(SourceAdapter $source, array $data, string $url, ?string $pet, ?string $category): array
    {
        $config = (array) config('app.import');
        $inferred = [];
        $context = $data['name'] . ' ' . $url;
        if ($pet === null) {
            $pet = Classifier::pet($context);
            $inferred[] = 'mascota';
        }
        if ($category === null) {
            $category = Classifier::category($context);
            $inferred[] = 'categoría';
        }
        if ($pet === null || $category === null) {
            return ['action' => 'skipped', 'message' => 'No se pudo determinar la mascota o la categoría. Indícalas con --pet y --category o en el archivo de URL.'];
        }

        $existing = Product::findBySource($source->key(), $url);
        $now = Database::now();
        $sourceFields = [
            'source_price'    => $data['price'],
            'source_currency' => $data['currency'],
            'imported_at'     => $now,
        ];

        if ($existing !== null) {
            // Solo se refrescan los datos de referencia; no se pisan los cambios del administrador.
            if (!$this->dryRun) {
                Product::update((int) $existing['id'], $sourceFields);
            }

            return ['action' => 'updated', 'message' => 'Se actualizaron precio de referencia y fecha de importación de "' . (string) $existing['name'] . '".'];
        }

        $description = ($config['copy_descriptions'] === true && $data['description'] !== null)
            ? str_limit($data['description'], 2000)
            : 'Descripción pendiente: redacta un texto propio antes de publicar. Datos de referencia importados de ' . $source->name() . '.';

        $image = ($config['use_remote_images'] === true && $data['image'] !== null && str_starts_with($data['image'], 'https://'))
            ? substr($data['image'], 0, 500)
            : '';

        $record = array_merge($sourceFields, [
            'sku'         => 'IMP-' . strtoupper(substr(sha1($source->key() . '|' . $url), 0, 12)),
            'name'        => $data['name'],
            'slug'        => $this->dryRun ? slugify($data['name']) : Product::uniqueSlug($data['name']),
            'brand'       => $data['brand'] ?? 'Marca no indicada en la fuente',
            'pet_type'    => $pet,
            'category'    => $category,
            'description' => $description,
            'price'       => '0.00',
            'currency'    => (string) config('app.currency'),
            'stock'       => 0,
            'image_url'   => $image,
            'source_url'  => $url,
            'source_site' => $source->key(),
            'data_origin' => 'scraped',
            'active'      => 0,
        ]);

        if (!$this->dryRun) {
            Product::create($record);
        }

        $note = $inferred !== [] ? ' (' . implode(' y ', $inferred) . ' inferida: revisar)' : '';

        return ['action' => 'created', 'message' => 'Creado inactivo "' . $data['name'] . '"' . $note . '. Define precio local y stock en el panel para publicarlo.'];
    }

    /**
     * @param array<string, string> $row
     * @return array{action: string, message: string}
     */
    public function importFileRow(array $row): array
    {
        $storeCurrency = (string) config('app.currency');
        $get = static fn (string $key): string => trim($row[$key] ?? '');

        $errors = [];
        $sku = strtoupper($get('sku'));
        if (preg_match('/^[A-Z0-9._-]{3,64}$/', $sku) !== 1) {
            $errors[] = 'SKU inválido (3 a 64 caracteres: letras, números, . _ -)';
        }
        $name = $get('name');
        if (mb_strlen($name, 'UTF-8') < 3 || mb_strlen($name, 'UTF-8') > 200) {
            $errors[] = 'nombre de 3 a 200 caracteres';
        }
        $brand = $get('brand');
        if (mb_strlen($brand, 'UTF-8') < 2 || mb_strlen($brand, 'UTF-8') > 120) {
            $errors[] = 'marca de 2 a 120 caracteres';
        }
        $pet = $this->resolvePet($get('pet_type'));
        if ($pet === null) {
            $errors[] = 'pet_type debe ser uno de: ' . implode(', ', array_keys(pets()));
        }
        $category = $this->resolveCategory($get('category'));
        if ($category === null) {
            $errors[] = 'category debe ser uno de: ' . implode(', ', array_keys(categories()));
        }
        $description = $get('description');
        if (mb_strlen($description, 'UTF-8') < 10) {
            $errors[] = 'descripción de al menos 10 caracteres';
        }
        $price = $get('price') === '' ? '0' : str_replace(',', '.', $get('price'));
        if (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $price) !== 1) {
            $errors[] = 'precio con punto decimal y hasta 2 decimales';
        }
        $currency = strtoupper($get('currency') !== '' ? $get('currency') : $storeCurrency);
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $errors[] = 'moneda ISO de 3 letras';
        }
        $stock = $get('stock') === '' ? '0' : $get('stock');
        if (preg_match('/^\d{1,7}$/', $stock) !== 1) {
            $errors[] = 'stock entero mayor o igual a 0';
        }
        $origin = $get('data_origin') !== '' ? strtolower($get('data_origin')) : 'manual';
        if (!in_array($origin, ['manual', 'demo', 'scraped'], true)) {
            $errors[] = 'data_origin debe ser manual, demo o scraped';
        }
        $sourceUrl = $get('source_url');
        $sourceSite = $get('source_site');
        if ($origin === 'scraped' && ($sourceUrl === '' || $sourceSite === '')) {
            $errors[] = 'los datos "scraped" requieren source_url y source_site';
        }
        if ($sourceUrl !== '' && (filter_var($sourceUrl, FILTER_VALIDATE_URL) === false || strlen($sourceUrl) > 500)) {
            $errors[] = 'source_url inválida';
        }
        $sourcePrice = $get('source_price');
        if ($sourcePrice !== '' && preg_match('/^\d{1,10}(\.\d{1,2})?$/', $sourcePrice) !== 1) {
            $errors[] = 'source_price inválido';
        }
        $image = $get('image_url');
        if ($image !== '' && preg_match('#^https://#i', $image) !== 1
            && (preg_match('#^assets/images/[A-Za-z0-9/_.-]+$#', $image) !== 1 || str_contains($image, '..'))) {
            $errors[] = 'image_url debe ser https o una ruta dentro de public/assets/images';
        }

        if ($errors !== []) {
            return ['action' => 'error', 'message' => 'Fila descartada: ' . implode('; ', $errors) . '.'];
        }

        $wantsActive = $get('active') === '' || in_array(strtolower($get('active')), ['1', 'true', 'si', 'sí', 'yes'], true);
        $sellable = $currency === $storeCurrency && to_cents($price) > 0;
        $active = $wantsActive && $sellable;

        $record = [
            'sku'             => $sku,
            'name'            => $name,
            'brand'           => $brand,
            'pet_type'        => (string) $pet,
            'category'        => (string) $category,
            'description'     => mb_substr($description, 0, 5000, 'UTF-8'),
            'price'           => cents_to_decimal(to_cents($price)),
            'currency'        => $currency,
            'stock'           => (int) $stock,
            'image_url'       => substr($image, 0, 500),
            'data_origin'     => $origin,
            'source_url'      => $sourceUrl !== '' ? $sourceUrl : null,
            'source_site'     => $sourceSite !== '' ? mb_substr($sourceSite, 0, 100, 'UTF-8') : null,
            'source_price'    => $sourcePrice !== '' ? cents_to_decimal(to_cents($sourcePrice)) : null,
            'source_currency' => $get('source_currency') !== '' ? strtoupper(substr($get('source_currency'), 0, 3)) : null,
            'imported_at'     => $origin === 'scraped' ? Database::now() : null,
            'active'          => $active ? 1 : 0,
        ];
        $warning = ($wantsActive && !$sellable)
            ? ' Quedó inactivo: el precio debe ser mayor que cero y en ' . $storeCurrency . '.'
            : '';

        $existing = Product::findBySku($sku);
        if ($existing !== null) {
            if (!$this->dryRun) {
                Product::update((int) $existing['id'], $record);
            }

            return ['action' => 'updated', 'message' => 'Actualizado "' . $name . '".' . $warning];
        }

        $record['slug'] = $this->dryRun ? slugify($name) : Product::uniqueSlug($name);
        if (!$this->dryRun) {
            Product::create($record);
        }

        return ['action' => 'created', 'message' => 'Creado "' . $name . '".' . $warning];
    }

    private function resolvePet(string $value): ?string
    {
        $value = strtolower($value);
        if (isset(pets()[$value])) {
            return $value;
        }

        return pet_by_slug($value);
    }

    private function resolveCategory(string $value): ?string
    {
        $value = strtolower($value);
        if (isset(categories()[$value])) {
            return $value;
        }

        return category_by_slug($value);
    }
}
