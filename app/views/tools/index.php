<?php
$tools = [
    ['/herramientas/edad', 'cake', 'Calculadora de edad', 'Convierte la edad de tu perro o gato a años humanos según su tamaño.'],
    ['/herramientas/alimentos-peligrosos', 'apple', 'Alimentos peligrosos', 'Consulta el nivel de riesgo de alimentos comunes, como el chocolate, para cada especie.'],
    ['/herramientas/peso-saludable', 'scale', 'Peso saludable', 'Evalúa su condición corporal y estima un peso de referencia.'],
];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Herramientas para tu mascota</h1>
            <p class="page-intro">Calculadoras rápidas y gratuitas. Dan estimaciones orientativas: para decisiones de salud consulta a tu veterinario.</p>
        </div>
    </header>
    <ul class="tool-grid">
        <?php foreach ($tools as [$path, $iconName, $name, $text]): ?>
            <li class="tool-card">
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
