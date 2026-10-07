<?php
/**
 * Dirección de entrega con formato flexible (los campos vacíos se omiten).
 *
 * @var array<string, string> $address
 */
$cityLine = implode(', ', array_filter([
    trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? '')),
    $address['region'] ?? '',
], static fn (string $v): bool => trim($v) !== ''));
?>
<address class="address">
    <?= e($address['line1'] ?? '') ?><br>
    <?php if (($address['line2'] ?? '') !== ''): ?><?= e($address['line2']) ?><br><?php endif; ?>
    <?php if ($cityLine !== ''): ?><?= e($cityLine) ?><br><?php endif; ?>
    <?php if (($address['country'] ?? '') !== ''): ?><?= e(country_name($address['country'])) ?><?php endif; ?>
    <?php if (($address['references'] ?? '') !== ''): ?>
        <span class="address-ref">Referencias: <?= e($address['references']) ?></span>
    <?php endif; ?>
</address>
