<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Descarga y consulta robots.txt por dominio antes de cada URL.
 *  - 200: se aplican sus reglas y su Crawl-delay.
 *  - 4xx (salvo 429): no hay robots.txt; se permite (RFC 9309).
 *  - 5xx, 429 o error de red: se asume que NO se permite (criterio prudente).
 */
final class RobotsGuard
{
    /** @var array<string, array{robots: ?RobotsTxt, allow_all: bool, reason: string}> */
    private array $hosts = [];

    public function __construct(private HttpClient $http)
    {
    }

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function check(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
            return ['allowed' => false, 'reason' => 'URL no válida.'];
        }

        $key = $scheme . '://' . $host;
        if (!isset($this->hosts[$key])) {
            $this->hosts[$key] = $this->load($key);
        }
        $entry = $this->hosts[$key];

        if ($entry['robots'] === null) {
            return ['allowed' => $entry['allow_all'], 'reason' => $entry['reason']];
        }

        $path = (string) ($parts['path'] ?? '/');
        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?' . $parts['query'];
        }
        $allowed = $entry['robots']->isAllowed($this->http->userAgent(), $path);
        $this->http->respectCrawlDelay($entry['robots']->crawlDelay($this->http->userAgent()));

        return [
            'allowed' => $allowed,
            'reason'  => $allowed ? 'Permitido por robots.txt.' : 'Bloqueado por robots.txt (' . $path . ').',
        ];
    }

    /**
     * @return array{robots: ?RobotsTxt, allow_all: bool, reason: string}
     */
    private function load(string $origin): array
    {
        try {
            $response = $this->http->get($origin . '/robots.txt');
        } catch (BlockedException $e) {
            throw $e;
        } catch (ImportException $e) {
            return ['robots' => null, 'allow_all' => false, 'reason' => 'No se pudo leer robots.txt (' . $e->getMessage() . '); por prudencia no se extrae.'];
        }

        $status = $response['status'];
        if ($status === 200) {
            return ['robots' => RobotsTxt::parse($response['body']), 'allow_all' => false, 'reason' => ''];
        }
        if ($status >= 400 && $status < 500 && $status !== 429) {
            return ['robots' => null, 'allow_all' => true, 'reason' => 'El sitio no publica robots.txt (HTTP ' . $status . ').'];
        }

        return ['robots' => null, 'allow_all' => false, 'reason' => 'robots.txt respondió HTTP ' . $status . '; por prudencia no se extrae.'];
    }
}
