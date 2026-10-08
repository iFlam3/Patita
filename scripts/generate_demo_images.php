<?php

declare(strict_types=1);

/*
 * Genera las ilustraciones locales de demostración (public/assets/images/demo)
 * combinando el icono de cada mascota con el de cada categoría (7 × 9 = 63
 * archivos SVG) más un placeholder genérico. Son imágenes propias: no se usan
 * fotos ni imágenes de las fuentes externas.
 *
 * Uso: php scripts/generate_demo_images.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$sprite = (string) file_get_contents(PUBLIC_PATH . '/assets/images/icons.svg');
$symbols = [];
if (preg_match_all('#<symbol id="i-([a-z0-9-]+)"[^>]*>(.*?)</symbol>#s', $sprite, $matches, PREG_SET_ORDER) > 0) {
    foreach ($matches as $m) {
        $symbols[$m[1]] = $m[2];
    }
}

$outDir = PUBLIC_PATH . '/assets/images/demo';
if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fwrite(STDERR, "No se pudo crear $outDir\n");
    exit(1);
}

/**
 * @param string $petPaths      Contenido del símbolo de la mascota.
 * @param string|null $catPaths Contenido del símbolo de la categoría.
 */
$render = static function (string $petPaths, ?string $catPaths): string {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img">'
        . '<rect width="400" height="300" fill="#F5F7F3"/>'
        . '<circle cx="200" cy="138" r="96" fill="#E4F1E6"/>'
        . '<path d="M64 252H336" stroke="#D8DED5" stroke-width="4" stroke-linecap="round"/>'
        . '<g transform="translate(128 66) scale(6)" fill="none" stroke="#1B6E2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
        . $petPaths . '</g>';
    if ($catPaths !== null) {
        $svg .= '<circle cx="306" cy="214" r="42" fill="#FFFFFF" stroke="#D8DED5" stroke-width="2"/>'
            . '<g transform="translate(276 184) scale(2.5)" fill="none" stroke="#231F20" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
            . $catPaths . '</g>';
    }

    return $svg . '</svg>' . "\n";
};

$count = 0;
foreach (pets() as $petCode => $pet) {
    $petIcon = $symbols[(string) $pet['icon']] ?? null;
    if ($petIcon === null) {
        fwrite(STDERR, "Falta el icono {$pet['icon']} en el sprite.\n");
        exit(1);
    }
    foreach (categories() as $categoryCode => $category) {
        $catIcon = $symbols[$category['icon']] ?? null;
        if ($catIcon === null) {
            fwrite(STDERR, "Falta el icono {$category['icon']} en el sprite.\n");
            exit(1);
        }
        file_put_contents($outDir . '/' . $petCode . '-' . $categoryCode . '.svg', $render($petIcon, $catIcon));
        $count++;
    }
}

file_put_contents($outDir . '/placeholder.svg', $render($symbols['paw-print'] ?? '', null));
$count++;

echo "Generadas $count ilustraciones en public/assets/images/demo\n";
