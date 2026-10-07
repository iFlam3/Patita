<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lector mínimo de archivos .env (KEY=VALUE).
 *
 * Soporta comentarios (#), comillas simples o dobles y valores vacíos.
 * Las variables reales del entorno del sistema tienen prioridad sobre el archivo.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                continue;
            }

            self::$values[$key] = self::parseValue(trim($value));
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $system = getenv($key);
        if ($system !== false) {
            return $system;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);

        return ($value !== null && is_numeric($value)) ? (int) $value : $default;
    }

    /**
     * @return list<string>
     */
    public static function list(string $key, string $default = ''): array
    {
        $raw = self::get($key, $default) ?? '';
        $items = array_map('trim', explode(',', $raw));

        return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
    }

    private static function parseValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        if (($first === '"' || $first === "'") && str_ends_with($value, $first) && strlen($value) >= 2) {
            $inner = substr($value, 1, -1);

            return $first === '"' ? str_replace(['\\n', '\\"'], ["\n", '"'], $inner) : $inner;
        }

        // Comentario al final de la línea: VALOR # comentario
        $hash = strpos($value, ' #');
        if ($hash !== false) {
            $value = rtrim(substr($value, 0, $hash));
        }

        return $value;
    }
}
