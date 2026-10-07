<?php
/**
 * @var array<string, mixed>|null $product
 * @var array<string, string>     $values
 * @var array<string, string>     $errors
 * @var list<string>              $brands
 * @var list<string>              $currencies
 */
$isNew = $product === null;
$action = $isNew ? url('/admin/productos') : url('/admin/productos/' . (int) $product['id']);
$storeCurrency = (string) config('app.currency');
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Administración', '/admin'], ['Productos', '/admin/productos'], [$isNew ? 'Nuevo' : (string) $product['name'], null]]]) ?>
    <header class="page-head page-head-actions">
        <div>
            <h1><?= $isNew ? 'Nuevo producto' : 'Editar producto' ?></h1>
            <?php if (!$isNew && (int) $product['active'] === 1): ?>
                <p class="page-intro"><a href="<?= e(url('/producto/' . $product['slug'])) ?>">Ver en la tienda</a></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="admin-form-layout">
        <form class="panel admin-form" action="<?= e($action) ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <fieldset class="form-section">
                <legend>Identificación</legend>
                <div class="field-row">
                    <div class="field">
                        <label for="p-sku">SKU</label>
                        <input id="p-sku" name="sku" type="text" required maxlength="64" value="<?= e($values['sku']) ?>"<?= field_attrs('sku', $errors, 'p-sku-hint') ?>>
                        <p class="field-hint" id="p-sku-hint">Único. Letras, números, punto y guiones.</p>
                        <?= field_error('sku', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="p-slug">Slug de la URL <span class="optional">(opcional)</span></label>
                        <input id="p-slug" name="slug" type="text" maxlength="200" value="<?= e($values['slug']) ?>"<?= field_attrs('slug', $errors, 'p-slug-hint') ?>>
                        <p class="field-hint" id="p-slug-hint">Si lo dejas vacío se genera desde el nombre.</p>
                        <?= field_error('slug', $errors) ?>
                    </div>
                </div>
                <div class="field">
                    <label for="p-name">Nombre</label>
                    <input id="p-name" name="name" type="text" required maxlength="200" value="<?= e($values['name']) ?>"<?= field_attrs('name', $errors) ?>>
                    <?= field_error('name', $errors) ?>
                </div>
                <div class="field">
                    <label for="p-brand">Marca</label>
                    <input id="p-brand" name="brand" type="text" required maxlength="120" list="marcas-existentes" value="<?= e($values['brand']) ?>"<?= field_attrs('brand', $errors) ?>>
                    <datalist id="marcas-existentes">
                        <?php foreach ($brands as $brand): ?><option value="<?= e($brand) ?>"></option><?php endforeach; ?>
                    </datalist>
                    <?= field_error('brand', $errors) ?>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Clasificación</legend>
                <div class="field-row">
                    <div class="field">
                        <label for="p-pet">Mascota</label>
                        <select id="p-pet" name="pet_type" required<?= field_attrs('pet_type', $errors) ?>>
                            <option value="">Elige una mascota</option>
                            <?php foreach (pets() as $code => $pet): ?>
                                <option value="<?= e($code) ?>"<?= $values['pet_type'] === $code ? ' selected' : '' ?>><?= e($pet['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('pet_type', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="p-category">Categoría</label>
                        <select id="p-category" name="category" required<?= field_attrs('category', $errors) ?>>
                            <option value="">Elige una categoría</option>
                            <?php foreach (categories() as $code => $category): ?>
                                <option value="<?= e($code) ?>"<?= $values['category'] === $code ? ' selected' : '' ?>><?= e($category['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('category', $errors) ?>
                    </div>
                </div>
                <div class="field">
                    <label for="p-description">Descripción</label>
                    <textarea id="p-description" name="description" rows="6" required maxlength="5000"<?= field_attrs('description', $errors, 'p-description-hint') ?>><?= e($values['description']) ?></textarea>
                    <p class="field-hint" id="p-description-hint">Describe el producto con datos verificables. No agregues indicaciones veterinarias ni beneficios de salud que no consten en la etiqueta del fabricante.</p>
                    <?= field_error('description', $errors) ?>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Precio y existencias</legend>
                <div class="field-row field-row-3">
                    <div class="field">
                        <label for="p-price">Precio de venta</label>
                        <input id="p-price" name="price" type="text" inputmode="decimal" required value="<?= e($values['price']) ?>"<?= field_attrs('price', $errors, 'p-price-hint') ?>>
                        <p class="field-hint" id="p-price-hint">Con punto decimal, por ejemplo 549.00.</p>
                        <?= field_error('price', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="p-currency">Moneda</label>
                        <select id="p-currency" name="currency"<?= field_attrs('currency', $errors) ?>>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= e($currency) ?>"<?= $values['currency'] === $currency ? ' selected' : '' ?>><?= e($currency) ?><?= $currency === $storeCurrency ? ' (tienda)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('currency', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="p-stock">Stock</label>
                        <input id="p-stock" name="stock" type="number" min="0" step="1" required value="<?= e($values['stock']) ?>"<?= field_attrs('stock', $errors) ?>>
                        <?= field_error('stock', $errors) ?>
                    </div>
                </div>
                <?php if (!$isNew && $product['source_price'] !== null): ?>
                    <p class="inline-notice"><?= icon('info', 'icon icon-sm') ?>
                        Precio de referencia en la fuente: <?= e($product['source_price']) ?> <?= e($product['source_currency']) ?>.
                        No se convierte automáticamente: define tu precio local de venta.</p>
                <?php endif; ?>
            </fieldset>

            <fieldset class="form-section">
                <legend>Imagen y origen</legend>
                <div class="field">
                    <label for="p-image">Imagen <span class="optional">(opcional)</span></label>
                    <input id="p-image" name="image_url" type="text" maxlength="500" value="<?= e($values['image_url']) ?>"<?= field_attrs('image_url', $errors, 'p-image-hint') ?>>
                    <p class="field-hint" id="p-image-hint">URL https con permiso de uso o ruta dentro de public/, por ejemplo assets/images/demo/dog-food.svg. Vacío = ilustración según mascota y categoría.</p>
                    <?= field_error('image_url', $errors) ?>
                </div>
                <div class="field">
                    <label for="p-origin">Origen de los datos</label>
                    <select id="p-origin" name="data_origin"<?= field_attrs('data_origin', $errors) ?>>
                        <option value="manual"<?= $values['data_origin'] === 'manual' ? ' selected' : '' ?>>Ingresado manualmente</option>
                        <option value="demo"<?= $values['data_origin'] === 'demo' ? ' selected' : '' ?>>Dato de demostración</option>
                        <?php if (!$isNew && $product['data_origin'] === 'scraped'): ?>
                            <option value="scraped"<?= $values['data_origin'] === 'scraped' ? ' selected' : '' ?>>Extraído de una fuente externa</option>
                        <?php endif; ?>
                    </select>
                    <?= field_error('data_origin', $errors) ?>
                </div>
                <?php if (!$isNew && $product['source_url'] !== null): ?>
                    <p class="field-hint">Fuente: <?= e($product['source_site']) ?>, importado el <?= e(format_datetime((string) $product['imported_at'])) ?>.<br>
                        <span class="break-all"><?= e($product['source_url']) ?></span></p>
                <?php endif; ?>
            </fieldset>

            <div class="field-check">
                <input type="hidden" name="active" value="">
                <input id="p-active" type="checkbox" name="active" value="1"<?= $values['active'] === '1' ? ' checked' : '' ?><?= field_attrs('active', $errors) ?>>
                <label for="p-active">Publicado en la tienda</label>
            </div>
            <?= field_error('active', $errors) ?>

            <div class="form-actions">
                <a class="link-back" href="<?= e(url('/admin/productos')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Volver a productos</a>
                <button type="submit" class="btn btn-primary"><?= $isNew ? 'Crear producto' : 'Guardar cambios' ?></button>
            </div>
        </form>

        <?php if (!$isNew): ?>
            <aside class="panel admin-danger" aria-labelledby="zona-eliminar">
                <h2 id="zona-eliminar">Eliminar producto</h2>
                <p>Prefiere desactivarlo: lo oculta de la tienda sin perder datos. Si lo eliminas, los pedidos anteriores conservan nombre, SKU y precio.</p>
                <form action="<?= e(url('/admin/productos/' . (int) $product['id'] . '/eliminar')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger" data-confirm="¿Eliminar definitivamente &quot;<?= e($product['name']) ?>&quot;? Esta acción no se puede deshacer.">Eliminar definitivamente</button>
                </form>
            </aside>
        <?php endif; ?>
    </div>
</div>
