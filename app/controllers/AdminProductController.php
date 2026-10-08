<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Product;
use App\Services\ImageUpload;
use RuntimeException;

/**
 * CRUD sencillo de productos.
 */
final class AdminProductController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        $q = mb_substr($request->query('q'), 0, 100, 'UTF-8');
        $status = $request->query('estado');
        if (!in_array($status, ['', 'activos', 'inactivos', 'sin-stock', 'importados'], true)) {
            $status = '';
        }

        return $this->view('admin/products/index', [
            'title'  => 'Productos',
            'q'      => $q,
            'status' => $status,
            'result' => Product::adminSearch($q, $status, max(1, (int) $request->query('pagina', '1'))),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params): Response
    {
        return $this->form(null);
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params): Response
    {
        [$input, $data, $errors] = $this->validate($request, null);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/productos/nuevo', $input, $errors);
        }

        $id = Product::create($data);
        Session::flash('success', 'Creaste el producto "' . (string) $data['name'] . '".');

        return $this->redirect('/admin/productos/' . $id . '/editar');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params): Response
    {
        return $this->form($this->findOrFail($params));
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $product = $this->findOrFail($params);
        [$input, $data, $errors] = $this->validate($request, $product);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/productos/' . (int) $product['id'] . '/editar', $input, $errors);
        }

        Product::update((int) $product['id'], $data);
        Session::flash('success', 'Guardaste los cambios de "' . (string) $data['name'] . '".');

        return $this->redirect('/admin/productos/' . (int) $product['id'] . '/editar');
    }

    /**
     * Activa o desactiva (eliminación lógica).
     *
     * @param array<string, string> $params
     */
    public function toggle(Request $request, array $params): Response
    {
        $product = $this->findOrFail($params);
        $activate = (int) $product['active'] !== 1;

        if ($activate && ((string) $product['currency'] !== (string) config('app.currency') || to_cents((string) $product['price']) <= 0)) {
            Session::flash('error', 'Para activar el producto define un precio mayor que cero en ' . (string) config('app.currency') . '.');

            return $this->redirect('/admin/productos/' . (int) $product['id'] . '/editar');
        }

        Product::setActive((int) $product['id'], $activate);
        Session::flash('success', $activate ? 'Activaste el producto: ya aparece en la tienda.' : 'Desactivaste el producto: ya no aparece en la tienda.');

        return $this->redirect(safe_path($request->input('return_to'), '/admin/productos'));
    }

    /**
     * Eliminación definitiva. Las líneas de pedidos conservan su copia histórica.
     *
     * @param array<string, string> $params
     */
    public function destroy(Request $request, array $params): Response
    {
        $product = $this->findOrFail($params);
        Product::delete((int) $product['id']);
        ImageUpload::delete((string) $product['image_url']);
        Session::flash('success', 'Eliminaste "' . (string) $product['name'] . '". Los pedidos anteriores conservan su detalle.');

        return $this->redirect('/admin/productos');
    }

    /**
     * @param array<string, mixed>|null $product
     */
    private function form(?array $product): Response
    {
        $currency = (string) config('app.currency');
        $values = [
            'sku'         => (string) ($product['sku'] ?? ''),
            'name'        => (string) ($product['name'] ?? ''),
            'slug'        => (string) ($product['slug'] ?? ''),
            'brand'       => (string) ($product['brand'] ?? ''),
            'pet_type'    => (string) ($product['pet_type'] ?? ''),
            'category'    => (string) ($product['category'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'price'       => (string) ($product['price'] ?? ''),
            'currency'    => (string) ($product['currency'] ?? $currency),
            'stock'       => (string) ($product['stock'] ?? '0'),
            'image_url'   => (string) ($product['image_url'] ?? ''),
            'data_origin' => (string) ($product['data_origin'] ?? 'manual'),
            'active'      => $product === null ? '1' : ((int) $product['active'] === 1 ? '1' : ''),
        ];
        $hasOld = Session::errors() !== [];
        foreach (array_keys($values) as $key) {
            if ($hasOld) {
                $values[$key] = old($key, $key === 'active' ? '' : $values[$key]);
            }
        }

        return $this->view('admin/products/form', [
            'title'      => $product === null ? 'Nuevo producto' : 'Editar producto',
            'product'    => $product,
            'values'     => $values,
            'errors'     => Session::errors(),
            'brands'     => Product::distinctBrands(),
            'currencies' => array_values(array_unique([$currency, $values['currency']])),
        ]);
    }

    /**
     * @param array<string, mixed>|null $product
     * @return array{0: array<string, string>, 1: array<string, mixed>, 2: array<string, string>}
     */
    private function validate(Request $request, ?array $product): array
    {
        $storeCurrency = (string) config('app.currency');
        $fields = ['sku', 'name', 'slug', 'brand', 'pet_type', 'category', 'description', 'price', 'currency', 'stock', 'image_url', 'data_origin', 'active'];
        $input = [];
        foreach ($fields as $field) {
            $input[$field] = $request->input($field);
        }
        $input['sku'] = strtoupper($input['sku']);
        $input['slug'] = strtolower($input['slug']);
        $input['active'] = $input['active'] === '1' ? '1' : '';

        $allowedOrigins = ['manual', 'demo'];
        if ($product !== null && $product['data_origin'] === 'scraped') {
            $allowedOrigins[] = 'scraped';
        }
        $allowedCurrencies = array_values(array_unique([$storeCurrency, (string) ($product['currency'] ?? $storeCurrency)]));

        $v = new Validator($input);
        $v->required('sku', 'el SKU')
            ->required('name', 'el nombre')->length('name', 'El nombre', 3, 200)
            ->required('brand', 'la marca')->length('brand', 'La marca', 2, 120)
            ->in('pet_type', array_keys(pets()), 'Elige una mascota de la lista.')
            ->in('category', array_keys(categories()), 'Elige una categoría de la lista.')
            ->required('description', 'una descripción')->length('description', 'La descripción', 10, 5000)
            ->required('price', 'el precio')->decimal('price', 'El precio', 0, 9999999999.99)
            ->in('currency', $allowedCurrencies, 'Moneda no válida.')
            ->required('stock', 'el stock')->integer('stock', 'El stock', 0, 1000000)
            ->in('data_origin', $allowedOrigins, 'Origen de datos no válido.');

        $productId = $product !== null ? (int) $product['id'] : null;
        if ($input['sku'] !== '' && preg_match('/^[A-Z0-9._-]{3,64}$/', $input['sku']) !== 1) {
            $v->addError('sku', 'El SKU admite de 3 a 64 caracteres: letras, números, punto, guion y guion bajo.');
        } elseif ($input['sku'] !== '' && Product::skuExists($input['sku'], $productId)) {
            $v->addError('sku', 'Ya existe otro producto con ese SKU.');
        }

        if ($input['slug'] !== '') {
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug']) !== 1 || strlen($input['slug']) > 200) {
                $v->addError('slug', 'El slug solo admite minúsculas, números y guiones (máximo 200).');
            }
        }

        $image = $input['image_url'];
        if ($image !== '') {
            $isRemote = preg_match('#^https://#i', $image) === 1 && filter_var($image, FILTER_VALIDATE_URL) !== false;
            $isLocal = preg_match('#^(assets/images|uploads/products)/[A-Za-z0-9/_.-]+\.(svg|png|jpe?g|webp)$#', $image) === 1
                && !str_contains($image, '..') && is_file(PUBLIC_PATH . '/' . $image);
            if ((!$isRemote && !$isLocal) || strlen($image) > 500) {
                $v->addError('image_url', 'Usa una URL https, una ruta existente dentro de public/ (por ejemplo assets/images/demo/dog-food.svg) o sube un archivo.');
            }
        }

        if ($input['active'] === '1' && !$v->fails()
            && ($input['currency'] !== $storeCurrency || to_cents($input['price']) <= 0)) {
            $v->addError('active', 'Para publicar el producto el precio debe ser mayor que cero y en ' . $storeCurrency . '.');
        }

        $upload = $request->file('image_file');
        if ($upload !== null && !$v->fails()) {
            try {
                $image = ImageUpload::store($upload);
                if ($product !== null && (string) $product['image_url'] !== $image) {
                    ImageUpload::delete((string) $product['image_url']);
                }
            } catch (RuntimeException $e) {
                $v->addError('image_file', $e->getMessage());
            }
        }

        if ($v->fails()) {
            return [$input, [], $v->errors()];
        }

        if ($input['slug'] === '') {
            $slug = Product::uniqueSlug($input['name'], $productId);
        } else {
            $slug = Product::uniqueSlug($input['slug'], $productId);
        }

        $data = [
            'sku'         => $input['sku'],
            'name'        => $input['name'],
            'slug'        => $slug,
            'brand'       => $input['brand'],
            'pet_type'    => $input['pet_type'],
            'category'    => $input['category'],
            'description' => $input['description'],
            'price'       => cents_to_decimal(to_cents($input['price'])),
            'currency'    => $input['currency'],
            'stock'       => (int) $input['stock'],
            'image_url'   => $image,
            'data_origin' => $input['data_origin'],
            'active'      => $input['active'] === '1' ? 1 : 0,
        ];

        return [$input, $data, []];
    }

    /**
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function findOrFail(array $params): array
    {
        $product = Product::find($this->intParam($params['id'] ?? ''));
        if ($product === null) {
            throw new HttpException(404, 'Ese producto no existe.');
        }

        return $product;
    }
}
