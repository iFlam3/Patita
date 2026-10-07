<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Lee productos desde JSON o CSV (alternativa al scraping).
 *
 * JSON: una lista de objetos o {"products": [...]}.
 * CSV:  primera fila con encabezados; separador "," o ";" (detectado);
 *       admite UTF-8 con o sin BOM.
 * Encabezados reconocidos: sku, name, brand, pet_type, category, description,
 * price, currency, stock, image_url, data_origin, active, source_url,
 * source_site, source_price, source_currency.
 */
final class FileImportReader
{
    private const MAX_BYTES = 10_485_760;
    private const MAX_ROWS = 5000;

    /**
     * @return list<array<string, string>>
     */
    public static function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ImportException('No se encontró el archivo: ' . $path);
        }
        if ((int) filesize($path) > self::MAX_BYTES) {
            throw new ImportException('El archivo supera 10 MB.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => self::readJson($path),
            'csv'  => self::readCsv($path),
            default => throw new ImportException('Formato no soportado: usa .json o .csv.'),
        };
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readJson(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            throw new ImportException('El JSON no es válido: ' . json_last_error_msg());
        }
        if (isset($data['products']) && is_array($data['products'])) {
            $data = $data['products'];
        }

        $rows = [];
        foreach (array_values($data) as $item) {
            if (!is_array($item)) {
                throw new ImportException('Cada producto del JSON debe ser un objeto.');
            }
            $row = [];
            foreach ($item as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                if (is_scalar($value) || $value === null) {
                    $row[strtolower(trim((string) $key))] = trim((string) $value);
                }
            }
            $rows[] = $row;
            if (count($rows) > self::MAX_ROWS) {
                throw new ImportException('El archivo supera ' . self::MAX_ROWS . ' productos.');
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ImportException('No se pudo abrir el CSV.');
        }

        try {
            $first = (string) fgets($handle);
            $first = (string) preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            $headers = array_map(
                static fn ($h): string => strtolower(trim((string) $h)),
                str_getcsv(rtrim($first, "\r\n"), $delimiter, '"', '\\')
            );
            if ($headers === [] || in_array('', $headers, true)) {
                throw new ImportException('La primera fila del CSV debe tener encabezados no vacíos.');
            }

            $rows = [];
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                if ($values === [null] || $values === []) {
                    continue; // línea vacía
                }
                $values = array_map(static fn ($v): string => trim((string) $v), $values);
                $values = array_pad(array_slice($values, 0, count($headers)), count($headers), '');
                $rows[] = array_combine($headers, $values);
                if (count($rows) > self::MAX_ROWS) {
                    throw new ImportException('El archivo supera ' . self::MAX_ROWS . ' productos.');
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
