<?php
/**
 * @var array<string, mixed>       $product
 * @var list<array<string, mixed>> $related
 * @var int                        $maxQty
 */
$pet = (string) $product['pet_type'];
$category = (string) $product['category'];
$petSlug = pet_slug($pet);
$stock = (int) $product['stock'];
$origin = (string) $product['data_origin'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [
        [pet_label($pet), '/mascotas/' . $petSlug],
        [category_label($category), '/mascotas/' . $petSlug . '?categoria=' . category_slug($category)],
        [(string) $product['name'], null],
    ]]) ?>

    <article class="product-detail">
        <div class="product-detail-media">
            <img src="<?= e(product_image((string) $product['image_url'], $pet, $category)) ?>"
                 alt="Ilustración de <?= e($product['name']) ?>" width="800" height="600" decoding="async">
            <?php if ($origin === 'demo'): ?>
                <span class="tag tag-demo">Demo</span>
            <?php endif; ?>
        </div>

        <div class="product-detail-info">
            <a class="product-detail-brand" href="<?= e(url('/marcas/' . slugify((string) $product['brand']))) ?>"><?= e($product['brand']) ?></a>
            <h1 class="product-detail-title"><?= e($product['name']) ?></h1>

            <p class="product-detail-price"><?= price_tag(to_cents((string) $product['price']), (string) $product['currency']) ?></p>

            <?php if ($stock <= 0): ?>
                <div class="stock-message stock-message-out" role="status">
                    <?= icon('circle-alert') ?>
                    <div>
                        <p class="stock-message-title">Sin existencias por ahora</p>
                        <p>No puedes agregarlo al carrito. Revisa los productos relacionados más abajo.</p>
                    </div>
                </div>
            <?php else: ?>
                <p class="stock <?= $stock <= 5 ? 'stock-low' : 'stock-in' ?>">
                    <?= $stock <= 5 ? 'Quedan ' . $stock . ($stock === 1 ? ' unidad' : ' unidades') : 'Disponible: ' . $stock . ' unidades' ?>
                </p>

                <form class="buy-form" action="<?= e(url('/carrito/agregar')) ?>" method="post" data-add-to-cart>
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="return_to" value="/producto/<?= e($product['slug']) ?>">
                    <div class="qty" data-qty>
                        <label for="cantidad">Cantidad</label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Quitar una unidad"><?= icon('minus', 'icon icon-sm') ?></button>
                            <input id="cantidad" type="number" name="quantity" value="1" min="1" max="<?= $maxQty ?>" step="1" inputmode="numeric" required>
                            <button type="button" class="qty-btn" data-qty-step="1" aria-label="Agregar una unidad"><?= icon('plus', 'icon icon-sm') ?></button>
                        </div>
                        <p class="field-hint">Máximo <?= $maxQty ?> por pedido.</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block"><?= icon('shopping-bag', 'icon icon-sm') ?> Agregar al carrito</button>
                </form>
            <?php endif; ?>

            <dl class="spec-list">
                <div><dt>Mascota</dt><dd><a href="<?= e(url('/mascotas/' . $petSlug)) ?>"><?= e(pet_label($pet)) ?></a></dd></div>
                <div><dt>Categoría</dt><dd><?= e(category_label($category)) ?></dd></div>
                <div><dt>Marca</dt><dd><?= e($product['brand']) ?></dd></div>
                <div><dt>SKU</dt><dd><?= e($product['sku']) ?></dd></div>
            </dl>

            <?php if ($origin === 'demo'): ?>
                <p class="origin-note"><?= icon('info', 'icon icon-sm') ?> Producto de demostración: marca ficticia, precio y existencias son valores de prueba, no información comercial verificada.</p>
            <?php elseif ($origin === 'scraped'): ?>
                <p class="origin-note"><?= icon('info', 'icon icon-sm') ?> Datos de referencia importados de una fuente externa el <?= e(format_datetime((string) $product['imported_at'], 'd/m/Y')) ?>. El precio y el stock de esta tienda son propios.</p>
            <?php endif; ?>
        </div>
    </article>

    <section class="product-description" aria-labelledby="descripcion-titulo">
        <h2 id="descripcion-titulo">Descripción</h2>
        <div class="prose">
            <?php foreach (preg_split('/\R{2,}/', trim((string) $product['description'])) ?: [] as $paragraph): ?>
                <p><?= nl2br(e($paragraph)) ?></p>
            <?php endforeach; ?>
        </div>
        <p class="field-hint">Si necesitas indicaciones de uso, alimentación o salud, consulta la etiqueta del fabricante o a un veterinario. No mostramos datos que no tenemos.</p>
    </section>

    <?php if ($related !== []): ?>
        <section class="section-related" aria-labelledby="relacionados-titulo">
            <h2 id="relacionados-titulo">También para <?= e(mb_strtolower(pet_label($pet), 'UTF-8')) ?></h2>
            <div class="product-grid">
                <?php foreach ($related as $item): ?>
                    <?= App\Core\View::partial('partials/product-card', ['product' => $item, 'returnTo' => '/producto/' . $product['slug']]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
