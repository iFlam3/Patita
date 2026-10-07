<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Resumen de una ejecución del importador, guardado como JSON en
 * storage/imports (sin tablas adicionales). El panel muestra los últimos.
 */
final class ImportReport
{
    /** @var list<array{action: string, ref: string, message: string}> */
    private array $entries = [];

    /** @var array<string, int> */
    private array $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 0];

    private string $startedAt;
    private ?string $stoppedReason = null;

    public function __construct(private string $mode, private string $source, private bool $dryRun)
    {
        $this->startedAt = gmdate('Y-m-d H:i:s');
    }

    public function add(string $action, string $ref, string $message): void
    {
        if (!isset($this->counts[$action])) {
            $action = 'error';
        }
        $this->counts[$action]++;
        $this->entries[] = ['action' => $action, 'ref' => $ref, 'message' => $message];
    }

    public function stop(string $reason): void
    {
        $this->stoppedReason = $reason;
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    public function summaryLine(): string
    {
        return sprintf(
            '%s%d creados, %d actualizados, %d omitidos, %d con error.%s',
            $this->dryRun ? '[simulación] ' : '',
            $this->counts['created'],
            $this->counts['updated'],
            $this->counts['skipped'],
            $this->counts['error'],
            $this->stoppedReason !== null ? ' Detenido: ' . $this->stoppedReason : ''
        );
    }

    /**
     * Guarda el reporte y devuelve la ruta del archivo.
     */
    public function save(): string
    {
        $data = [
            'mode'        => $this->mode,
            'source'      => $this->source,
            'dry_run'     => $this->dryRun,
            'started_at'  => $this->startedAt,
            'finished_at' => gmdate('Y-m-d H:i:s'),
            'counts'      => $this->counts,
            'stopped'     => $this->stoppedReason,
            'entries'     => array_slice($this->entries, 0, 1000),
        ];

        $file = BASE_PATH . '/storage/imports/import-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        log_message('import', $this->mode . ' ' . $this->source . ': ' . $this->summaryLine(), ['report' => basename($file)]);
        foreach ($this->entries as $entry) {
            if ($entry['action'] === 'error') {
                log_message('import', 'Error en ' . $entry['ref'] . ': ' . $entry['message']);
            }
        }

        return $file;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit): array
    {
        $files = glob(BASE_PATH . '/storage/imports/import-*.json') ?: [];
        rsort($files);
        $reports = [];
        foreach (array_slice($files, 0, $limit) as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $data['file'] = basename($file);
                $reports[] = $data;
            }
        }

        return $reports;
    }
}
