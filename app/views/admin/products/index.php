<?php
/**
 * @var string               $q
 * @var string               $status
 * @var array<string, mixed> $result
 */
$query = array_filter(['q' => $q, 'estado' => $status], static fn (string $v): bool => $v !== '');
$returnTo = '/admin/productos' . ($query !== [] || $result['page'] > 1 ? '?' . http_build_query($query + ($result['page'] > 1 ? ['pagina' => $result['page']] : [])) : '');
$storeCurrency = (string) config('app.currency');
$statusOptions = ['' => 'Todos', 'activos' => 'Publicados', 'inactivos' => 'Inactivos', 'sin-stock' => 'Sin stock', 'importados' => 'Importados'];
?>
<div class="container page">
    <header class="page-head page-head-actions">
        <div>
            <h1>Productos</h1>
            <p class="page-intro"><?= (int) $result['total'] ?> producto(s) en total, incluidos los inactivos.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/admin/productos/nuevo')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nuevo producto</a>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'productos']) ?>

    <form class="admin-filter" action="<?= e(url('/admin/productos')) ?>" method="get" role="search">
        <div class="field">
            <label for="a-q">Buscar por nombre, SKU o marca</label>
            <input id="a-q" type="search" name="q" value="<?= e($q) ?>" maxlength="100">
        </div>
        <div class="field">
            <label for="a-estado">Mostrar</label>
            <select id="a-estado" name="estado">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-quiet">Filtrar</button>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="state state-empty state-compact">
            <h2>No hay productos con ese filtro</h2>
            <a class="btn btn-quiet" href="<?= e(url('/admin/productos')) ?>">Ver todos</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-admin">
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Mascota y categoría</th>
                        <th scope="col" class="num">Precio</th>
                        <th scope="col" class="num">Stock</th>
                        <th scope="col">Origen</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $p): ?>
                        <tr>
                            <th scope="row">
                                <a href="<?= e(url('/admin/productos/' . (int) $p['id'] . '/editar')) ?>"><?= e($p['name']) ?></a>
                                <span class="cell-sub"><?= e($p['sku']) ?>, <?= e($p['brand']) ?></span>
                            </th>
                            <td><?= e(pet_label((string) $p['pet_type'])) ?><span class="cell-sub"><?= e(category_label((string) $p['category'])) ?></span></td>
                            <td class="num">
                                <?= price_tag(to_cents((string) $p['price']), (string) $p['currency']) ?>
                                <?php if ($p['currency'] !== $storeCurrency): ?><span class="cell-sub cell-warn">Moneda distinta</span><?php endif; ?>
                                <?php if ($p['source_price'] !== null): ?>
                                    <span class="cell-sub">Ref. origen: <?= e($p['source_price']) ?> <?= e($p['source_currency']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (int) $p['stock'] ?></td>
                            <td><span class="tag tag-<?= e($p['data_origin']) ?>"><?= e(['scraped' => 'Extraído', 'demo' => 'Demo', 'manual' => 'Manual'][$p['data_origin']] ?? $p['data_origin']) ?></span></td>
                            <td>
                                <form action="<?= e(url('/admin/productos/' . (int) $p['id'] . '/estado')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                    <button type="submit" class="toggle<?= (int) $p['active'] === 1 ? ' is-on' : '' ?>" aria-label="<?= (int) $p['active'] === 1 ? 'Desactivar' : 'Activar' ?> <?= e($p['name']) ?>">
                                        <?= (int) $p['active'] === 1 ? 'Publicado' : 'Inactivo' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="cell-actions">
                                <a class="btn btn-quiet btn-sm" href="<?= e(url('/admin/productos/' . (int) $p['id'] . '/editar')) ?>">Editar<span class="sr-only"> <?= e($p['name']) ?></span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('partials/pagination', [
            'page' => $result['page'], 'pages' => $result['pages'], 'base' => '/admin/productos', 'query' => $query,
        ]) ?>
    <?php endif; ?>
</div>
