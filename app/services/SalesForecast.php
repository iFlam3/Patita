<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Regresión lineal simple por mínimos cuadrados: y = a + b·x, con x = 0, 1, 2…
 * (número de periodo) e y = ingresos del periodo.
 */
final class SalesForecast
{
    /**
     * @param list<float> $ys
     * @return array{a: float, b: float, r2: float|null} r2 es null si hay menos de 3 puntos o y es constante.
     */
    public static function fit(array $ys): array
    {
        $n = count($ys);
        if ($n === 0) {
            return ['a' => 0.0, 'b' => 0.0, 'r2' => null];
        }
        $meanX = ($n - 1) / 2;
        $meanY = array_sum($ys) / $n;
        $sxy = 0.0;
        $sxx = 0.0;
        foreach ($ys as $x => $y) {
            $sxy += ($x - $meanX) * ($y - $meanY);
            $sxx += ($x - $meanX) ** 2;
        }
        $b = $sxx > 0 ? $sxy / $sxx : 0.0;
        $a = $meanY - $b * $meanX;

        $ssTot = 0.0;
        $ssRes = 0.0;
        foreach ($ys as $x => $y) {
            $ssTot += ($y - $meanY) ** 2;
            $ssRes += ($y - ($a + $b * $x)) ** 2;
        }

        return ['a' => $a, 'b' => $b, 'r2' => ($n >= 3 && $ssTot > 0) ? 1 - $ssRes / $ssTot : null];
    }

    /**
     * Valores de la recta para los periodos n, n+1… (sin bajar de cero).
     *
     * @param array{a: float, b: float} $model
     * @return list<float>
     */
    public static function project(array $model, int $from, int $periods): array
    {
        $out = [];
        for ($x = $from; $x < $from + $periods; $x++) {
            $out[] = max(0.0, $model['a'] + $model['b'] * $x);
        }

        return $out;
    }
}
