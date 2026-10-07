<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Registro de adaptadores disponibles.
 */
final class Sources
{
    /**
     * @return array<string, SourceAdapter>
     */
    public static function all(): array
    {
        $adapters = [new PetstoreDirectAdapter(), new PetsmartAdapter()];
        $byKey = [];
        foreach ($adapters as $adapter) {
            $byKey[$adapter->key()] = $adapter;
        }

        return $byKey;
    }

    public static function get(string $key): ?SourceAdapter
    {
        return self::all()[$key] ?? null;
    }

    public static function isAllowed(string $key): bool
    {
        return in_array($key, (array) config('app.import.allowed_sources', []), true);
    }
}
