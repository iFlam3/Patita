<?php $photos = (array) config('photos', []); ?>
<div class="container page">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Créditos de imágenes', 'intro' => $photos !== [] ? 'Fotos de Wikimedia Commons usadas según su licencia (CC0, dominio público, CC BY o CC BY-SA). Gracias a sus autores.' : 'Recursos de terceros usados en el sitio.']) ?>

    <ul class="credit-grid">
        <?php foreach ($photos as $path => $credit): ?>
            <li class="credit-card">
                <img src="<?= e(asset($path)) ?>" alt="" width="320" height="240" loading="lazy" decoding="async">
                <p class="credit-title"><?= e(str_limit($credit['title'], 80)) ?></p>
                <p class="field-hint"><?= App\Core\View::partial('partials/photo-credit', ['credit' => $credit]) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <ul class="plain-list">
        <li>Iconos: <a href="https://lucide.dev" rel="noopener" target="_blank">Lucide</a> (licencia ISC) y <a href="https://tabler.io/icons" rel="noopener" target="_blank">Tabler Icons</a> (licencia MIT).</li>
        <li>Tipografía: <a href="https://fonts.google.com/specimen/Archivo" rel="noopener" target="_blank">Archivo</a> (SIL Open Font License).</li>
        <li>Fotos de mascotas y productos: propias del proyecto.</li>
    </ul>
</div>
