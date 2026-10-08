<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Cálculos de las herramientas interactivas. Son estimaciones orientativas:
 * no reemplazan la evaluación de un veterinario.
 */
final class PetTools
{
    /**
     * Edad equivalente en años humanos.
     * Primer año ≈ 15, segundo ≈ +9; luego +4 por año en gatos y entre +4 y +7
     * en perros según el tamaño (los perros grandes envejecen más rápido).
     */
    public static function humanAge(string $species, float $years, string $dogSize = 'mediano'): float
    {
        $years = max(0.0, $years);
        $rate = 4;
        if ($species === 'perro') {
            $rate = (int) (config('tools.dog_sizes.' . $dogSize . '.rate') ?? 5);
        }

        if ($years <= 1) {
            $human = 15 * $years;
        } elseif ($years <= 2) {
            $human = 15 + 9 * ($years - 1);
        } else {
            $human = 24 + $rate * ($years - 2);
        }

        return round($human, 1);
    }

    /**
     * Nivel de riesgo de un alimento para una mascota.
     *
     * @return array{level: string, label: string, class: string, note: string, food: string}|null
     */
    public static function toxicity(string $pet, string $food): ?array
    {
        $data = config('tools.foods.' . $food);
        if (!is_array($data) || !isset(pets()[$pet])) {
            return null;
        }
        $level = (string) ($data['levels'][$pet] ?? 'nodata');
        $info = (array) config('tools.levels.' . $level);

        return [
            'level' => $level,
            'label' => (string) $info['label'],
            'class' => (string) $info['class'],
            'note'  => (string) $data['note'],
            'food'  => (string) $data['label'],
        ];
    }

    /**
     * Peso ideal aproximado a partir de la condición corporal (escala 1–9).
     * Regla de uso veterinario: cada punto por encima de 5 equivale a cerca de
     * un 10 % de exceso de peso. Para valores bajo 5 no se calcula una cifra.
     *
     * @return array{status: string, ideal: ?float, excess_percent: int}
     */
    public static function healthyWeight(float $weightKg, int $bodyCondition): array
    {
        if ($bodyCondition > 5) {
            $excess = 10 * ($bodyCondition - 5);

            return ['status' => 'over', 'ideal' => round($weightKg * 100 / (100 + $excess), 1), 'excess_percent' => $excess];
        }
        if ($bodyCondition < 5) {
            return ['status' => 'under', 'ideal' => null, 'excess_percent' => 0];
        }

        return ['status' => 'ideal', 'ideal' => round($weightKg, 1), 'excess_percent' => 0];
    }
}
