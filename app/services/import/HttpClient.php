<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Cliente HTTP prudente para el importador (cURL):
 *  - User-Agent descriptivo configurable (IMPORT_USER_AGENT).
 *  - Timeouts de conexión y de respuesta, tamaño máximo de 5 MB.
 *  - Pausa mínima entre solicitudes (IMPORT_DELAY_SECONDS o Crawl-delay).
 *  - Reintentos limitados solo ante errores de red, 429 o 5xx, con espera
 *    creciente y respeto de Retry-After (hasta 60 s).
 *  - Caché en disco (storage/cache/http) para no repetir descargas.
 *  - Si detecta CAPTCHA o bloqueo anti-bot lanza BlockedException y no reintenta.
 *  - Sin proxies, sin rotación de User-Agent, sin cookies de sesión.
 */
final class HttpClient
{
    private const MAX_BYTES = 5_242_880;

    private float $lastRequestAt = 0.0;
    private float $delay;

    public function __construct(
        private string $userAgent,
        private int $timeout,
        float $delaySeconds,
        private int $maxRetries,
        private int $cacheTtlSeconds,
        private string $cacheDir,
    ) {
        $this->delay = max(1.0, $delaySeconds);
    }

    public static function fromConfig(): self
    {
        $c = (array) config('app.import');

        return new self(
            (string) $c['user_agent'],
            (int) $c['timeout_seconds'],
            (float) $c['delay_seconds'],
            (int) $c['max_retries'],
            (int) $c['cache_ttl_hours'] * 3600,
            BASE_PATH . '/storage/cache/http',
        );
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Aumenta la pausa si robots.txt pide un Crawl-delay mayor.
     */
    public function respectCrawlDelay(?float $seconds): void
    {
        if ($seconds !== null) {
            $this->delay = max($this->delay, min($seconds, 120.0));
        }
    }

    /**
     * @return array{status: int, body: string, url: string, from_cache: bool}
     */
    public function get(string $url, bool $useCache = true): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new ImportException('URL no válida: ' . $url);
        }

        $cacheFile = $this->cacheDir . '/' . sha1($url) . '.json';
        if ($useCache && $this->cacheTtlSeconds > 0 && is_file($cacheFile)
            && (time() - (int) filemtime($cacheFile)) < $this->cacheTtlSeconds) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['status'], $cached['body'])) {
                return ['status' => (int) $cached['status'], 'body' => (string) $cached['body'], 'url' => $url, 'from_cache' => true];
            }
        }

        $attempt = 0;
        while (true) {
            $this->throttle();
            $response = $this->request($url);

            if ($response['error'] !== '') {
                if ($attempt < $this->maxRetries) {
                    $this->wait($this->backoff($attempt++));
                    continue;
                }
                throw new ImportException('Error de red al descargar ' . $url . ': ' . $response['error']);
            }

            $finalHost = strtolower((string) parse_url($response['url'], PHP_URL_HOST));
            if ($finalHost !== $host) {
                throw new ImportException('La URL redirigió a otro dominio (' . $finalHost . '); se descarta.');
            }

            if (self::looksBlocked($response['status'], $response['body'])) {
                throw new BlockedException(sprintf(
                    'La fuente respondió con un bloqueo o desafío anti-bot (HTTP %d). El importador se detiene y no intenta eludirlo.',
                    $response['status']
                ));
            }

            if (in_array($response['status'], [429, 500, 502, 503, 504], true) && $attempt < $this->maxRetries) {
                $retryAfter = $response['retry_after'];
                $this->wait($retryAfter !== null ? min(60.0, max(1.0, $retryAfter)) : $this->backoff($attempt));
                $attempt++;
                continue;
            }

            break;
        }

        if ($response['status'] === 200 && $this->cacheTtlSeconds > 0) {
            @file_put_contents($cacheFile, (string) json_encode([
                'url' => $url, 'status' => 200, 'fetched_at' => gmdate('c'), 'body' => $response['body'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX);
        }

        return ['status' => $response['status'], 'body' => $response['body'], 'url' => $response['url'], 'from_cache' => false];
    }

    /**
     * Señales típicas de CAPTCHA o protección anti-bot.
     */
    public static function looksBlocked(int $status, string $body): bool
    {
        $lower = strtolower($body);
        // Señales explícitas de desafío. "captcha" a secas no basta en una
        // respuesta 200: muchas fichas normales cargan reCAPTCHA para sus
        // formularios de contacto o boletín.
        $challengeMarkers = [
            'cf-chl', 'challenge-platform', 'px-captcha', 'perimeterx', 'datadome', '_incapsula_',
            'pardon our interruption', 'are you a robot', 'verify you are human', 'access denied',
        ];
        $hasChallenge = false;
        foreach ($challengeMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                $hasChallenge = true;
                break;
            }
        }

        if (in_array($status, [401, 403, 429, 503], true)) {
            return $status === 403 || $hasChallenge || str_contains($lower, 'captcha');
        }

        // Página 200 que en realidad es un desafío: pequeña, con señal explícita
        // y sin datos estructurados de producto.
        return $status === 200 && $hasChallenge && strlen($body) < 150000
            && !str_contains($lower, 'application/ld+json');
    }

    /**
     * @return array{status: int, body: string, url: string, error: string, retry_after: ?float}
     */
    private function request(string $url): array
    {
        $handle = curl_init($url);
        if ($handle === false) {
            return ['status' => 0, 'body' => '', 'url' => $url, 'error' => 'No se pudo iniciar cURL', 'retry_after' => null];
        }

        $body = '';
        $retryAfter = null;
        $tooLarge = false;

        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => min(10, $this->timeout),
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_USERAGENT       => $this->userAgent,
            CURLOPT_ENCODING        => '',
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_HTTPHEADER      => [
                'Accept: text/html,application/xhtml+xml,text/plain;q=0.9,*/*;q=0.5',
                'Accept-Language: es-419,es;q=0.9,en;q=0.6',
            ],
            CURLOPT_HEADERFUNCTION  => static function ($ch, string $header) use (&$retryAfter): int {
                if (stripos($header, 'Retry-After:') === 0) {
                    $value = trim(substr($header, 12));
                    $retryAfter = is_numeric($value) ? (float) $value : null;
                }

                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION   => static function ($ch, string $chunk) use (&$body, &$tooLarge): int {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                    $tooLarge = true;

                    return 0; // Aborta la descarga.
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);

        curl_exec($handle);
        $error = curl_errno($handle) !== 0 ? curl_error($handle) : '';
        if ($tooLarge) {
            $error = 'la respuesta supera 5 MB';
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $finalUrl = (string) curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        // Desde PHP 8 el manejador se libera solo (curl_close está deprecado en 8.5).
        unset($handle);
        $this->lastRequestAt = microtime(true);

        return ['status' => $status, 'body' => $body, 'url' => $finalUrl !== '' ? $finalUrl : $url, 'error' => $error, 'retry_after' => $retryAfter];
    }

    private function throttle(): void
    {
        if ($this->lastRequestAt <= 0.0) {
            return;
        }
        $elapsed = microtime(true) - $this->lastRequestAt;
        if ($elapsed < $this->delay) {
            $this->wait($this->delay - $elapsed);
        }
    }

    private function backoff(int $attempt): float
    {
        return min(60.0, $this->delay * (2 ** ($attempt + 1)));
    }

    private function wait(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }
}
