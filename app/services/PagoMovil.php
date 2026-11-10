<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Datos de Pago Móvil que ve el cliente. El administrador los edita desde el
 * panel; se guardan en storage/settings/pago_movil.json (sin tabla extra).
 */
final class PagoMovil
{
    /** Datos de demostración (ficticios). */
    public const DEFAULTS = [
        'bank'    => '0102 - Banco de Venezuela',
        'id_doc'  => 'V-12.345.678',
        'phone'   => '0412-5550123',
        'holder'  => 'Patita (demo)',
    ];

    public const LABELS = [
        'bank'   => 'Banco',
        'id_doc' => 'Cédula / RIF',
        'phone'  => 'Teléfono',
        'holder' => 'Titular',
    ];

    private static function file(): string
    {
        return BASE_PATH . '/storage/settings/pago_movil.json';
    }

    /**
     * @return array{bank: string, id_doc: string, phone: string, holder: string}
     */
    public static function get(): array
    {
        $data = [];
        if (is_file(self::file())) {
            $decoded = json_decode((string) file_get_contents(self::file()), true);
            $data = is_array($decoded) ? $decoded : [];
        }
        $out = self::DEFAULTS;
        foreach (self::DEFAULTS as $key => $_) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                $out[$key] = $data[$key];
            }
        }

        return $out;
    }

    /**
     * @param array<string, string> $data
     */
    public static function save(array $data): void
    {
        $clean = array_intersect_key($data, self::DEFAULTS);
        $dir = dirname(self::file());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $tmp = self::file() . '.tmp';
        if (file_put_contents($tmp, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false
            || !rename($tmp, self::file())) {
            throw new \RuntimeException('No se pudieron guardar los datos de Pago Móvil.');
        }
    }

    /** Texto plano para correos. */
    public static function text(): string
    {
        $lines = [];
        foreach (self::get() as $key => $value) {
            $lines[] = self::LABELS[$key] . ': ' . $value;
        }

        return implode("\n", $lines);
    }
}
