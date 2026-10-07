<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Productos. La parte pública solo ve productos activos, con precio mayor que
 * cero y en la moneda configurada de la tienda (nunca se mezclan monedas).
 */
final class Product
{
    public const SORTS = [
        'recientes'   => 'p.created_at DESC, p.id DESC',
        'precio_asc'  => 'p.price ASC, p.name ASC',
        'precio_desc' => 'p.price DESC, p.name ASC',
        'nombre_asc'  => 'p.name ASC, p.id ASC',
        'nombre_desc' => 'p.name DESC, p.id DESC',
    ];

    public const SORT_LABELS = [
        'recientes'   => 'Más recientes',
        'precio_asc'  => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
        'nombre_asc'  => 'Nombre: A a Z',
        'nombre_desc' => 'Nombre: Z a A',
    ];

    /** Columnas editables desde el panel o el importador. */
    private const FIELDS = [
        'sku', 'name', 'slug', 'brand', 'pet_type', 'category', 'description', 'price', 'currency', 'stock',
        'image_url', 'source_url', 'source_site', 'source_price', 'source_currency', 'data_origin',
        'imported_at', 'active',
    ];

    // ------------------------------------------------------------ catálogo

    /**
     * @param array{q?: string, pet?: ?string, brand?: ?string, category?: ?string, min?: ?int, max?: ?int, in_stock?: bool} $filters
     *        min/max en centavos.
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function search(array $filters, string $sort, int $page, int $perPage): array
    {
        [$where, $params] = self::publicFilters($filters);
        $pdo = Database::pdo();

        $count = $pdo->prepare('SELECT COUNT(*) FROM products p WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $order = self::SORTS[$sort] ?? self::SORTS['recientes'];

        $stmt = $pdo->prepare('SELECT p.* FROM products p WHERE ' . $where . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?');
        $index = 1;
        foreach ($params as $value) {
            $stmt->bindValue($index++, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue($index++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($index, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items'    => $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'pages'    => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPublicBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM products WHERE slug = ? AND active = 1 AND currency = ? AND price > 0'
        );
        $stmt->execute([$slug, self::currency()]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Destacados: hasta 2 productos con stock por mascota, los más recientes.
     *
     * @return list<array<string, mixed>>
     */
    public static function featured(int $limit = 8): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM (
                SELECT p.*, ROW_NUMBER() OVER (PARTITION BY p.pet_type ORDER BY p.created_at DESC, p.id DESC) AS rn
                FROM products p
                WHERE p.active = 1 AND p.currency = ? AND p.price > 0 AND p.stock > 0
             ) ranked
             WHERE rn <= 2
             ORDER BY rn ASC, created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, self::currency());
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Relacionados: misma mascota, priorizando la misma categoría y con stock.
     *
     * @param array<string, mixed> $product
     * @return list<array<string, mixed>>
     */
    public static function related(array $product, int $limit = 4): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM products
             WHERE active = 1 AND currency = ? AND price > 0 AND id <> ? AND pet_type = ?
             ORDER BY (category = ?) DESC, (stock > 0) DESC, created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, self::currency());
        $stmt->bindValue(2, (int) $product['id'], PDO::PARAM_INT);
        $stmt->bindValue(3, (string) $product['pet_type']);
        $stmt->bindValue(4, (string) $product['category']);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Marcas con productos visibles (SELECT DISTINCT).
     *
     * @return list<string>
     */
    public static function distinctBrands(?string $petType = null): array
    {
        $sql = 'SELECT DISTINCT brand FROM products WHERE active = 1 AND currency = ? AND price > 0';
        $params = [self::currency()];
        if ($petType !== null) {
            $sql .= ' AND pet_type = ?';
            $params[] = $petType;
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY brand');
        $stmt->execute($params);

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Categorías con productos visibles (SELECT DISTINCT), en el orden de la lista controlada.
     *
     * @return list<string>
     */
    public static function distinctCategories(?string $petType = null, ?string $brand = null): array
    {
        $sql = 'SELECT DISTINCT category FROM products WHERE active = 1 AND currency = ? AND price > 0';
        $params = [self::currency()];
        if ($petType !== null) {
            $sql .= ' AND pet_type = ?';
            $params[] = $petType;
        }
        if ($brand !== null) {
            $sql .= ' AND brand = ?';
            $params[] = $brand;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $found = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        return array_values(array_filter(array_keys(categories()), static fn (string $c): bool => in_array($c, $found, true)));
    }

    /**
     * Resumen por marca para la página "Comprar por marca".
     *
     * @return list<array{brand: string, slug: string, total: int, demo_total: int, pets: list<string>}>
     */
    public static function brandSummary(): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT brand, COUNT(*) AS total, SUM(data_origin = 'demo') AS demo_total,
                    GROUP_CONCAT(DISTINCT pet_type ORDER BY pet_type SEPARATOR ',') AS pets
             FROM products
             WHERE active = 1 AND currency = ? AND price > 0
             GROUP BY brand
             ORDER BY brand"
        );
        $stmt->execute([self::currency()]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'brand'      => (string) $row['brand'],
                'slug'       => slugify((string) $row['brand']),
                'total'      => (int) $row['total'],
                'demo_total' => (int) $row['demo_total'],
                'pets'       => array_values(array_filter(explode(',', (string) $row['pets']))),
            ];
        }

        return $rows;
    }

    /**
     * Resuelve el nombre real de una marca a partir de su slug de URL.
     */
    public static function brandBySlug(string $slug): ?string
    {
        foreach (self::distinctBrands() as $brand) {
            if (slugify($brand) === $slug) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * @return array<string, int> pet_type => cantidad de productos visibles
     */
    public static function countsByPet(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT pet_type, COUNT(*) AS total FROM products
             WHERE active = 1 AND currency = ? AND price > 0 GROUP BY pet_type'
        );
        $stmt->execute([self::currency()]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['pet_type']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<string, int> category => cantidad para una mascota
     */
    public static function categoryCounts(string $petType): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT category, COUNT(*) AS total FROM products
             WHERE active = 1 AND currency = ? AND price > 0 AND pet_type = ? GROUP BY category'
        );
        $stmt->execute([self::currency(), $petType]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['category']] = (int) $row['total'];
        }

        return $counts;
    }

    // ------------------------------------------------------------ lectura general

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>> indexado por id
     */
    public static function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare('SELECT * FROM products WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['id']] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySku(string $sku): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM products WHERE sku = ?');
        $stmt->execute([$sku]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySource(string $site, string $url): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM products WHERE source_site = ? AND source_url = ?');
        $stmt->execute([$site, $url]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    // ------------------------------------------------------------ administración

    /**
     * Listado del panel: incluye inactivos y cualquier moneda.
     *
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function adminSearch(string $q, string $status, int $page, int $perPage = 20): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $like = '%' . self::escapeLike($q) . '%';
            $where[] = "(name LIKE ? ESCAPE '!' OR sku LIKE ? ESCAPE '!' OR brand LIKE ? ESCAPE '!')";
            array_push($params, $like, $like, $like);
        }
        if ($status === 'activos') {
            $where[] = 'active = 1';
        } elseif ($status === 'inactivos') {
            $where[] = 'active = 0';
        } elseif ($status === 'sin-stock') {
            $where[] = 'stock = 0';
        } elseif ($status === 'importados') {
            $where[] = "data_origin = 'scraped'";
        }
        $sqlWhere = implode(' AND ', $where);
        $pdo = Database::pdo();

        $count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE ' . $sqlWhere);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $stmt = $pdo->prepare('SELECT * FROM products WHERE ' . $sqlWhere . ' ORDER BY updated_at DESC, id DESC LIMIT ? OFFSET ?');
        $index = 1;
        foreach ($params as $value) {
            $stmt->bindValue($index++, $value);
        }
        $stmt->bindValue($index++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($index, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $data = array_intersect_key($data, array_flip(self::FIELDS));
        $now = Database::now();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $columns = array_keys($data);
        $sql = 'INSERT INTO products (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $data = array_intersect_key($data, array_flip(self::FIELDS));
        if ($data === []) {
            return;
        }
        $data['updated_at'] = Database::now();

        $sets = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($data)));
        $stmt = Database::pdo()->prepare('UPDATE products SET ' . $sets . ' WHERE id = ?');
        $stmt->execute([...array_values($data), $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::pdo()->prepare('UPDATE products SET active = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, Database::now(), $id]);
    }

    /**
     * Eliminación física. Las líneas de pedidos se conservan (product_id pasa a NULL).
     */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function skuExists(string $sku, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM products WHERE sku = ?';
        $params = [$sku];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Genera un slug único a partir de un texto base.
     */
    public static function uniqueSlug(string $text, ?int $exceptId = null): string
    {
        $base = substr(slugify($text), 0, 200) ?: 'producto';
        $slug = $base;
        $suffix = 2;
        while (true) {
            $sql = 'SELECT 1 FROM products WHERE slug = ?';
            $params = [$slug];
            if ($exceptId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $exceptId;
            }
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn() === false) {
                return $slug;
            }
            $slug = $base . '-' . $suffix++;
        }
    }

    /**
     * Indicadores del panel.
     *
     * @return array{active: int, inactive: int, out_of_stock: int, low_stock: int, other_currency: int, scraped: int, demo: int}
     */
    public static function stats(): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT
                SUM(active = 1) AS active,
                SUM(active = 0) AS inactive,
                SUM(active = 1 AND stock = 0) AS out_of_stock,
                SUM(active = 1 AND stock BETWEEN 1 AND 5) AS low_stock,
                SUM(currency <> ?) AS other_currency,
                SUM(data_origin = 'scraped') AS scraped,
                SUM(data_origin = 'demo') AS demo
             FROM products"
        );
        $stmt->execute([self::currency()]);
        $row = $stmt->fetch() ?: [];

        return [
            'active'         => (int) ($row['active'] ?? 0),
            'inactive'       => (int) ($row['inactive'] ?? 0),
            'out_of_stock'   => (int) ($row['out_of_stock'] ?? 0),
            'low_stock'      => (int) ($row['low_stock'] ?? 0),
            'other_currency' => (int) ($row['other_currency'] ?? 0),
            'scraped'        => (int) ($row['scraped'] ?? 0),
            'demo'           => (int) ($row['demo'] ?? 0),
        ];
    }

    // ------------------------------------------------------------ internos

    /**
     * @param array{q?: string, pet?: ?string, brand?: ?string, category?: ?string, min?: ?int, max?: ?int, in_stock?: bool} $f
     * @return array{0: string, 1: list<string>}
     */
    private static function publicFilters(array $f): array
    {
        $where = ['p.active = 1', 'p.currency = ?', 'p.price > 0'];
        $params = [self::currency()];

        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $terms = preg_split('/\s+/u', mb_substr($q, 0, 100, 'UTF-8')) ?: [];
            foreach (array_slice(array_filter($terms, static fn (string $t): bool => $t !== ''), 0, 5) as $term) {
                $like = '%' . self::escapeLike($term) . '%';
                $where[] = "(p.name LIKE ? ESCAPE '!' OR p.brand LIKE ? ESCAPE '!' OR p.description LIKE ? ESCAPE '!')";
                array_push($params, $like, $like, $like);
            }
        }
        if (!empty($f['pet'])) {
            $where[] = 'p.pet_type = ?';
            $params[] = (string) $f['pet'];
        }
        if (!empty($f['brand'])) {
            $where[] = 'p.brand = ?';
            $params[] = (string) $f['brand'];
        }
        if (!empty($f['category'])) {
            $where[] = 'p.category = ?';
            $params[] = (string) $f['category'];
        }
        if (isset($f['min']) && $f['min'] !== null) {
            $where[] = 'p.price >= ?';
            $params[] = cents_to_decimal((int) $f['min']);
        }
        if (isset($f['max']) && $f['max'] !== null) {
            $where[] = 'p.price <= ?';
            $params[] = cents_to_decimal((int) $f['max']);
        }
        if (!empty($f['in_stock'])) {
            $where[] = 'p.stock > 0';
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Escapa comodines de LIKE usando "!" como carácter de escape
     * (independiente del modo NO_BACKSLASH_ESCAPES).
     */
    private static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    private static function currency(): string
    {
        return (string) config('app.currency');
    }
}
