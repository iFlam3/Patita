<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Guías de cuidado', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Guías de cuidado</h1>
            <p class="page-intro">Lo básico para cuidar bien a cada especie: alimentación, higiene, entorno y salud preventiva. Son guías generales; tu veterinario es la referencia para tu mascota.</p>
        </div>
    </header>

    <ul class="pet-grid">
        <?php foreach (pets() as $code => $pet): ?>
            <?php $guide = (array) config('care.' . $code, []); ?>
            <li class="pet-card">
                <a href="<?= e(url('/cuidados/' . $pet['slug'])) ?>">
                    <span class="pet-card-icon"><?= icon((string) $pet['icon']) ?></span>
                    <span class="pet-card-text">
                        <span class="pet-card-name"><?= e($pet['label']) ?></span>
                        <span class="pet-card-sub"><?= e((string) ($guide['summary'] ?? '')) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <section class="tools-teaser" aria-labelledby="teaser-herramientas">
        <h2 id="teaser-herramientas">Herramientas para tu mascota</h2>
        <p>Calcula su edad en años humanos, revisa si un alimento es peligroso o estima su peso saludable.</p>
        <a class="btn btn-primary" href="<?= e(url('/herramientas')) ?>">Ver herramientas</a>
    </section>
</div>
