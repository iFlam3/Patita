<?php
/**
 * Atribución de una foto con licencia libre.
 *
 * @var array<string, string> $credit
 */
?>
Foto: <a href="<?= e($credit['source']) ?>" rel="noopener" target="_blank"><?= e(str_limit($credit['author'], 60)) ?></a>,
<?php if ($credit['license_url'] !== ''): ?><a href="<?= e($credit['license_url']) ?>" rel="noopener license" target="_blank"><?= e($credit['license']) ?></a><?php else: ?><?= e($credit['license']) ?><?php endif; ?>,
vía Wikimedia Commons
