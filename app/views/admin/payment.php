<?php
/**
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */
$fields = [
    'bank'   => ['Banco', 'text', 'Código y nombre, por ejemplo 0102 - Banco de Venezuela.'],
    'id_doc' => ['Cédula / RIF', 'text', 'Formato V-12345678.'],
    'phone'  => ['Teléfono', 'tel', 'Formato 0412-1234567.'],
    'holder' => ['Titular', 'text', 'Nombre que verá el cliente.'],
];
?>
<div class="container page">
    <header class="page-head">
        <div>
            <h1>Pago Móvil</h1>
            <p class="page-intro">Datos que ven los clientes al confirmar un pedido con Pago Móvil.</p>
        </div>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'pago']) ?>

    <form class="panel admin-form" action="<?= e(url('/admin/pago-movil')) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <?php foreach ($fields as $name => [$label, $type, $hint]): ?>
            <div class="field">
                <label for="pm-<?= e($name) ?>"><?= e($label) ?></label>
                <input id="pm-<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" required maxlength="80" value="<?= e($values[$name]) ?>"<?= field_attrs($name, $errors, 'pm-' . $name . '-hint') ?>>
                <p class="field-hint" id="pm-<?= e($name) ?>-hint"><?= e($hint) ?></p>
                <?= field_error($name, $errors) ?>
            </div>
        <?php endforeach; ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
