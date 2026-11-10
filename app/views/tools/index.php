<?php
$tools = [
    ['/herramientas/edad', 'cake', 'Calculadora de edad', 'Convierte la edad de tu perro o gato a años humanos según su tamaño.'],
    ['/herramientas/alimentos-peligrosos', 'apple', 'Alimentos peligrosos', 'Consulta el nivel de riesgo de alimentos comunes, como el chocolate, para cada especie.'],
    ['/herramientas/peso-saludable', 'scale', 'Peso saludable', 'Evalúa su condición corporal y estima un peso de referencia.'],
];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', null]]]) ?>
    <header class="page-head page-banner">
        <div class="page-banner-text">
            <h1 data-split>Herramientas para tu mascota</h1>
            <p class="page-intro" data-reveal data-delay="3">Calculadoras rápidas y gratuitas. Dan estimaciones orientativas: para decisiones de salud consulta a tu veterinario.</p>
        </div>
        <div class="page-banner-media" aria-hidden="true">
            <img src="<?= e(asset('assets/images/hero/pug-480.jpg')) ?>" alt="" width="480" height="600" decoding="async" data-reveal="scale" data-delay="2">
            <img src="<?= e(asset('assets/images/hero/terrier-480.jpg')) ?>" alt="" width="480" height="600" decoding="async" data-reveal="scale" data-delay="3">
        </div>
    </header>
    <ul class="tool-grid tool-grid-page">
        <?php foreach ($tools as $n => [$path, $iconName, $name, $text]): ?>
            <li class="tool-card" data-reveal data-delay="<?= $n + 2 ?>">
                <a href="<?= e(url($path)) ?>">
                    <span class="tool-card-icon"><?= icon($iconName) ?></span>
                    <span class="tool-card-name"><?= e($name) ?></span>
                    <span class="tool-card-text"><?= e($text) ?></span>
                    <span class="link-more">Usar herramienta</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
