<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Plantillas PHP en app/views. Toda salida dinámica debe pasar por e().
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }

        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        if (preg_match('#^[a-z0-9/_-]+$#', $template) !== 1) {
            throw new RuntimeException('Nombre de vista inválido.');
        }

        $file = BASE_PATH . '/app/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $template);
        }

        $render = static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__file;
        };

        ob_start();
        try {
            $render($file, $data);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
