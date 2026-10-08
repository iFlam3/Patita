<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Subida de imágenes de producto desde el panel.
 *
 * - Solo JPEG, PNG o WebP, verificados por contenido (finfo + getimagesize),
 *   no por la extensión que envía el navegador.
 * - Máximo 3 MB y 5000 px por lado.
 * - Nombre aleatorio y extensión según el tipo real: el nombre original del
 *   archivo nunca se usa.
 * - Se guardan en public/uploads/products, donde public/uploads/.htaccess
 *   impide ejecutar scripts.
 */
final class ImageUpload
{
    public const DIR = 'uploads/products';
    private const MAX_BYTES = 3_145_728;
    private const MAX_SIDE = 5000;
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * @param array<string, mixed> $file Entrada de $_FILES.
     * @return string Ruta relativa a public/, por ejemplo uploads/products/ab12….jpg
     */
    public static function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('La imagen supera el tamaño máximo de 3 MB.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen. Inténtalo de nuevo.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Archivo de imagen no válido.');
        }
        if ((int) filesize($tmp) > self::MAX_BYTES) {
            throw new RuntimeException('La imagen supera el tamaño máximo de 3 MB.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extension = self::TYPES[$mime] ?? null;
        $size = @getimagesize($tmp);
        if ($extension === null || $size === false || ($size['mime'] ?? '') !== $mime) {
            throw new RuntimeException('Formato no permitido. Sube una imagen JPG, PNG o WebP.');
        }
        if ($size[0] < 1 || $size[1] < 1 || $size[0] > self::MAX_SIDE || $size[1] > self::MAX_SIDE) {
            throw new RuntimeException('La imagen debe medir como máximo 5000 × 5000 píxeles.');
        }

        $dir = PUBLIC_PATH . '/' . self::DIR;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        $relative = self::DIR . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($tmp, PUBLIC_PATH . '/' . $relative)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        return $relative;
    }

    /**
     * Borra una imagen subida previamente (solo dentro de uploads/products).
     */
    public static function delete(string $relative): void
    {
        if (preg_match('#^' . preg_quote(self::DIR, '#') . '/[a-f0-9]{32}\.(jpg|png|webp)$#', $relative) !== 1) {
            return;
        }
        $path = PUBLIC_PATH . '/' . $relative;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
