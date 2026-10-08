<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Acerca de ' . config('app.name'), 'intro' => 'Una tienda para todas las mascotas de la casa, y también para las de la granja.']) ?>

    <div class="prose-page">
        <p><?= e(config('app.name')) ?> reúne en un solo lugar alimento, accesorios e insumos para perros, gatos, aves, peces, reptiles, caballos, animales pequeños y animales de granja. Puedes comprar por mascota, por marca o buscar directamente lo que necesitas.</p>

        <h2>Lo que nos importa</h2>
        <ul class="care-list">
            <li><?= icon('check', 'icon icon-sm') ?><span><strong>Información clara.</strong> Si un dato del producto no está disponible, lo decimos. No prometemos beneficios de salud ni damos indicaciones veterinarias.</span></li>
            <li><?= icon('check', 'icon icon-sm') ?><span><strong>Precios sin sorpresas.</strong> Ves el costo de envío antes de confirmar y pagas por transferencia o al recibir.</span></li>
            <li><?= icon('check', 'icon icon-sm') ?><span><strong>Todas las especies.</strong> Desde estética canina y felina hasta comederos para gallinas y cuidado equino.</span></li>
            <li><?= icon('check', 'icon icon-sm') ?><span><strong>Bienestar animal.</strong> No vendemos animales vivos. Publicamos guías de cuidado y herramientas gratuitas para ayudarte a cuidar mejor.</span></li>
        </ul>

        <h2>Sigue explorando</h2>
        <div class="state-actions state-actions-left">
            <a class="btn btn-primary" href="<?= e(url('/mascotas')) ?>">Comprar por mascota</a>
            <a class="btn btn-quiet" href="<?= e(url('/cuidados')) ?>">Guías de cuidado</a>
            <a class="btn btn-quiet" href="<?= e(url('/contacto')) ?>">Contacto</a>
        </div>
    </div>
</div>
