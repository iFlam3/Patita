<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Limitador de intentos con archivos locales (storage/ratelimit).
 *
 * Se eligió archivos para no agregar una sexta tabla. Limitaciones conocidas
 * (documentadas en docs/PRODUCCION.md):
 *  - Solo funciona con un único servidor: varias instancias no comparten los
 *    contadores.
 *  - Depende de la IP de REMOTE_ADDR: usuarios detrás de la misma NAT comparten
 *    el límite por IP, y un atacante con muchas IP evita el límite por IP
 *    (aunque no el límite por correo+IP de cada combinación).
 *  - Un atacante puede crear muchos archivos pequeños; se purgan los vencidos
 *    de forma probabilística.
 *  - Si storage/ratelimit no es escribible, el límite falla de forma abierta
 *    (no bloquea) y se registra el error.
 */
final class RateLimiter
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? BASE_PATH . '/storage/ratelimit';
    }

    public function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return count($this->attempts($key, $decaySeconds)) >= $maxAttempts;
    }

    /**
     * Registra un intento y devuelve cuántos hay dentro de la ventana.
     */
    public function hit(string $key, int $decaySeconds): int
    {
        $file = $this->file($key);
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            log_message('security', 'No se pudo escribir el archivo de límite de intentos.', ['file' => basename($file)]);

            return 0;
        }

        try {
            flock($handle, LOCK_EX);
            $data = json_decode((string) stream_get_contents($handle), true);
            $now = time();
            $attempts = array_values(array_filter(
                is_array($data) ? $data : [],
                static fn ($t): bool => is_int($t) && $t > $now - $decaySeconds
            ));
            $attempts[] = $now;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($attempts));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        if (random_int(1, 50) === 1) {
            $this->purge(max($decaySeconds, 86400));
        }

        return count($attempts);
    }

    public function clear(string $key): void
    {
        $file = $this->file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Segundos hasta que vence el intento más antiguo de la ventana.
     */
    public function availableIn(string $key, int $decaySeconds): int
    {
        $attempts = $this->attempts($key, $decaySeconds);
        if ($attempts === []) {
            return 0;
        }

        return max(1, min($attempts) + $decaySeconds - time());
    }

    /**
     * @return list<int>
     */
    private function attempts(string $key, int $decaySeconds): array
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        $now = time();

        return array_values(array_filter(
            is_array($data) ? $data : [],
            static fn ($t): bool => is_int($t) && $t > $now - $decaySeconds
        ));
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $key) . '.json';
    }

    private function purge(int $olderThanSeconds): void
    {
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime < time() - $olderThanSeconds) {
                @unlink($file);
            }
        }
    }
}
