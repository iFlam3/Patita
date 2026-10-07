<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Intérprete de robots.txt según RFC 9309:
 *  - Grupos por User-agent (los grupos repetidos del mismo agente se combinan).
 *  - Se usa el grupo cuyo agente coincide con el token del producto del
 *    User-Agent; si no hay, el grupo "*".
 *  - Gana la regla más larga que coincide; ante empate gana Allow.
 *  - Soporta comodines "*" y el ancla final "$".
 */
final class RobotsTxt
{
    /**
     * @var list<array{agents: list<string>, rules: list<array{allow: bool, path: string}>, delay: ?float}>
     */
    private array $groups = [];

    public static function parse(string $content): self
    {
        $robots = new self();
        $current = null;
        $lastWasAgent = false;

        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            $hash = strpos($line, '#');
            if ($hash !== false) {
                $line = substr($line, 0, $hash);
            }
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if ($current === null || !$lastWasAgent) {
                    if ($current !== null) {
                        $robots->groups[] = $current;
                    }
                    $current = ['agents' => [], 'rules' => [], 'delay' => null];
                }
                $current['agents'][] = strtolower($value);
                $lastWasAgent = true;
                continue;
            }

            $lastWasAgent = false;
            if ($current === null) {
                continue;
            }

            if ($field === 'allow' || $field === 'disallow') {
                if ($value === '') {
                    continue; // "Disallow:" vacío no restringe nada.
                }
                $current['rules'][] = ['allow' => $field === 'allow', 'path' => $value];
            } elseif ($field === 'crawl-delay' && is_numeric($value)) {
                $current['delay'] = (float) $value;
            }
        }

        if ($current !== null) {
            $robots->groups[] = $current;
        }

        return $robots;
    }

    public function isAllowed(string $userAgent, string $path): bool
    {
        $path = $path === '' ? '/' : $path;
        if ($path === '/robots.txt') {
            return true;
        }

        $best = null;
        foreach ($this->rulesFor($userAgent) as $rule) {
            if (!self::matches($rule['path'], $path)) {
                continue;
            }
            $length = strlen($rule['path']);
            if ($best === null || $length > $best['length'] || ($length === $best['length'] && $rule['allow'])) {
                $best = ['length' => $length, 'allow' => $rule['allow']];
            }
        }

        return $best === null || $best['allow'];
    }

    public function crawlDelay(string $userAgent): ?float
    {
        $delay = null;
        foreach ($this->groupsFor($userAgent) as $group) {
            if ($group['delay'] !== null) {
                $delay = max($delay ?? 0.0, $group['delay']);
            }
        }

        return $delay;
    }

    /**
     * @return list<array{allow: bool, path: string}>
     */
    private function rulesFor(string $userAgent): array
    {
        $rules = [];
        foreach ($this->groupsFor($userAgent) as $group) {
            array_push($rules, ...$group['rules']);
        }

        return $rules;
    }

    /**
     * @return list<array{agents: list<string>, rules: list<array{allow: bool, path: string}>, delay: ?float}>
     */
    private function groupsFor(string $userAgent): array
    {
        $token = strtolower((string) preg_replace('#[/\s].*$#', '', trim($userAgent)));

        $specific = [];
        $wildcard = [];
        foreach ($this->groups as $group) {
            foreach ($group['agents'] as $agent) {
                if ($agent === '*') {
                    $wildcard[] = $group;
                    break;
                }
                if ($token !== '' && $agent === $token) {
                    $specific[] = $group;
                    break;
                }
            }
        }

        return $specific !== [] ? $specific : $wildcard;
    }

    private static function matches(string $pattern, string $path): bool
    {
        $anchored = str_ends_with($pattern, '$');
        if ($anchored) {
            $pattern = substr($pattern, 0, -1);
        }
        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . ($anchored ? '$' : '') . '#';

        return preg_match($regex, $path) === 1;
    }
}
