<?php
$messages = App\Core\Session::messages();
if ($messages === []) {
    return;
}
$icons = ['success' => 'check', 'error' => 'circle-alert', 'info' => 'info'];
?>
<div class="container flash-stack">
    <?php foreach ($messages as $flash): ?>
        <?php $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info'; ?>
        <div class="flash flash-<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
            <?= icon($icons[$type]) ?>
            <p><?= e($flash['message']) ?></p>
        </div>
    <?php endforeach; ?>
</div>
