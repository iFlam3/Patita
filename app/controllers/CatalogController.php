<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Product;

/**
 * Catálogo, comprar por mascota, comprar por marca y detalle de producto.
 * Las tres vistas de listado comparten filtros, orden y paginación; la única
 * diferencia es el filtro fijo (mascota o marca) que impone la URL.
 */
final class CatalogController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        return $this->listing($request, [
            'base'    => '/catalogo',
            'title'   => 'Catálogo',
            'heading' => 'Catálogo completo',
            'intro'   => 'Busca por nombre, marca o descripción y combina filtros para encontrar lo que necesitas.',
            'fixed'   => [],
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function pets(Request $request, array $params): Response
    {
        return $this->view('catalog/pets', [
            'title'     => 'Comprar por mascota',
            'petCounts' => Product::countsByPet(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function pet(Request $request, array $params): Response
    {
        $code = pet_by_slug($params['pet'] ?? '');
        if ($code === null) {
            throw new HttpException(404);
        }
        $pet = pets()[$code];

        return $this->listing($request, [
            'base'           => '/mascotas/' . $pet['slug'],
            'title'          => $pet['label'],
            'heading'        => $pet['label'],
            'intro'          => $pet['intro'],
            'fixed'          => ['pet' => $code],
            'pet'            => $code,
            'categoryCounts' => Product::categoryCounts($code),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function brands(Request $request, array $params): Response
    {
        return $this->view('catalog/brands', [
            'title'  => 'Comprar por marca',
            'brands' => Product::brandSummary(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function brand(Request $request, array $params): Response
    {
        $slug = $params['brand'] ?? '';
        $brand = Product::brandBySlug($slug);
        if ($brand === null) {
            throw new HttpException(404, 'No encontramos esa marca o ya no tiene productos disponibles.');
        }

        return $this->listing($request, [
            'base'    => '/marcas/' . $slug,
            'title'   => $brand,
            'heading' => $brand,
            'intro'   => 'Todos los productos disponibles de ' . $brand . '.',
            'fixed'   => ['brand' => $brand],
            'brand'   => $brand,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function product(Request $request, array $params): Response
    {
        $product = Product::findPublicBySlug($params['slug'] ?? '');
        if ($product === null) {
            throw new HttpException(404, 'Este producto no existe o ya no está disponible.');
        }

        return $this->view('product/show', [
            'title'       => (string) $product['name'],
            'description' => str_limit((string) $product['description'], 155),
            'product'     => $product,
            'related'     => Product::related($product, 4),
            'maxQty'      => min((int) $product['stock'], (int) config('app.shop.max_qty_per_item', 20)),
        ]);
    }

    /**
     * @param array{base: string, title: string, heading: string, intro: string, fixed: array<string, string>, pet?: string, brand?: string, categoryCounts?: array<string, int>} $context
     */
    private function listing(Request $request, array $context): Response
    {
        $fixed = $context['fixed'];
        $notices = [];

        // Lectura y saneo de filtros (los valores inválidos se ignoran).
        $q = mb_substr($request->query('q'), 0, 100, 'UTF-8');
        $petSlug = $request->query('mascota');
        $pet = $fixed['pet'] ?? ($petSlug !== '' ? pet_by_slug($petSlug) : null);
        $brandSlug = $request->query('marca');
        $brand = $fixed['brand'] ?? ($brandSlug !== '' ? Product::brandBySlug($brandSlug) : null);
        $categorySlug = $request->query('categoria');
        $category = $categorySlug !== '' ? category_by_slug($categorySlug) : null;
        $inStock = $request->query('disponibles') === '1';

        $minRaw = $request->query('precio_min');
        $maxRaw = $request->query('precio_max');
        $min = $this->parsePrice($minRaw);
        $max = $this->parsePrice($maxRaw);
        if (($minRaw !== '' && $min === null) || ($maxRaw !== '' && $max === null)) {
            $notices[] = 'Ignoramos un precio con formato inválido. Usa números, por ejemplo 250 o 250.50.';
        }
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
            $notices[] = 'Intercambiamos el precio mínimo y el máximo.';
        }

        $sort = $request->query('orden', 'recientes');
        if (!isset(Product::SORTS[$sort])) {
            $sort = 'recientes';
        }
        $page = max(1, (int) $request->query('pagina', '1'));
        $perPage = (int) config('app.shop.per_page', 12);

        $result = Product::search([
            'q'        => $q,
            'pet'      => $pet,
            'brand'    => $brand,
            'category' => $category,
            'min'      => $min,
            'max'      => $max,
            'in_stock' => $inStock,
        ], $sort, $page, $perPage);

        // Parámetros activos para enlaces (los fijos viven en la ruta).
        $query = array_filter([
            'q'          => $q,
            'mascota'    => isset($fixed['pet']) ? '' : ($pet !== null ? pet_slug($pet) : ''),
            'marca'      => isset($fixed['brand']) ? '' : ($brand !== null ? slugify($brand) : ''),
            'categoria'  => $category !== null ? category_slug($category) : '',
            'precio_min' => $min !== null ? cents_to_decimal($min) : '',
            'precio_max' => $max !== null ? cents_to_decimal($max) : '',
            'disponibles' => $inStock ? '1' : '',
            'orden'      => $sort !== 'recientes' ? $sort : '',
        ], static fn (string $v): bool => $v !== '');

        $data = [
            'title'          => $context['title'],
            'heading'        => $context['heading'],
            'intro'          => $context['intro'],
            'base'           => $context['base'],
            'fixed'          => $fixed,
            'contextPet'     => $context['pet'] ?? null,
            'contextBrand'   => $context['brand'] ?? null,
            'categoryCounts' => $context['categoryCounts'] ?? [],
            'result'         => $result,
            'query'          => $query,
            'notices'        => $notices,
            'chips'          => $this->chips($query, $context['base']),
            'filters'        => [
                'q' => $q, 'pet' => $pet, 'brand' => $brand, 'category' => $category,
                'min' => $min, 'max' => $max, 'in_stock' => $inStock, 'sort' => $sort,
            ],
            'brandOptions'    => Product::distinctBrands($fixed['pet'] ?? null),
            'categoryOptions' => Product::distinctCategories($fixed['pet'] ?? null, $fixed['brand'] ?? null),
        ];

        if ($request->query('parcial') === '1') {
            return Response::html(View::partial('catalog/results', $data));
        }

        return $this->view('catalog/index', $data);
    }

    /**
     * Precio de filtro en centavos, o null si está vacío o es inválido.
     */
    private function parsePrice(string $value): ?int
    {
        if ($value === '' || preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        return to_cents($value);
    }

    /**
     * Etiquetas de filtros activos con el enlace para quitarlos.
     *
     * @param array<string, string> $query
     * @return list<array{label: string, url: string}>
     */
    private function chips(array $query, string $base): array
    {
        $chips = [];
        foreach ($query as $key => $value) {
            $label = match ($key) {
                'q'           => 'Búsqueda: "' . $value . '"',
                'mascota'     => pet_label((string) pet_by_slug($value)),
                'marca'       => 'Marca: ' . (Product::brandBySlug($value) ?? $value),
                'categoria'   => category_label((string) category_by_slug($value)),
                'precio_min'  => 'Desde ' . format_money(to_cents($value)),
                'precio_max'  => 'Hasta ' . format_money(to_cents($value)),
                'disponibles' => 'Solo con stock',
                default       => null,
            };
            if ($label === null) {
                continue;
            }
            $rest = $query;
            unset($rest[$key]);
            $chips[] = ['label' => $label, 'url' => url($base, $rest)];
        }

        return $chips;
    }
}
