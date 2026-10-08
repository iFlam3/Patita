<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\PetTools;

/**
 * Guías de cuidado, herramientas interactivas y páginas informativas.
 * Las herramientas funcionan con formularios GET (sin JavaScript).
 */
final class PageController extends Controller
{
    private const STATIC_PAGES = [
        'acerca-de'           => ['view' => 'pages/about',   'title' => 'Acerca de Patita'],
        'preguntas-frecuentes' => ['view' => 'pages/faq',     'title' => 'Preguntas frecuentes'],
        'terminos'            => ['view' => 'pages/terms',   'title' => 'Términos y condiciones'],
        'privacidad'          => ['view' => 'pages/privacy', 'title' => 'Política de privacidad'],
        'devoluciones'        => ['view' => 'pages/returns', 'title' => 'Cambios y devoluciones'],
        'contacto'            => ['view' => 'pages/contact', 'title' => 'Contacto'],
    ];

    /**
     * @param array<string, string> $params
     */
    public function page(Request $request, array $params): Response
    {
        $page = self::STATIC_PAGES[$params['page'] ?? ''] ?? null;
        if ($page === null) {
            throw new HttpException(404);
        }

        return $this->view($page['view'], ['title' => $page['title']]);
    }

    /**
     * @param array<string, string> $params
     */
    public function careIndex(Request $request, array $params): Response
    {
        return $this->view('care/index', ['title' => 'Guías de cuidado']);
    }

    /**
     * @param array<string, string> $params
     */
    public function care(Request $request, array $params): Response
    {
        $code = pet_by_slug($params['pet'] ?? '');
        $guide = $code !== null ? config('care.' . $code) : null;
        if (!is_array($guide)) {
            throw new HttpException(404);
        }

        return $this->view('care/show', [
            'title' => 'Cuidado de ' . mb_strtolower(pet_label((string) $code), 'UTF-8'),
            'code'  => $code,
            'pet'   => pets()[$code],
            'guide' => $guide,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function tools(Request $request, array $params): Response
    {
        return $this->view('tools/index', ['title' => 'Herramientas para tu mascota']);
    }

    /**
     * @param array<string, string> $params
     */
    public function age(Request $request, array $params): Response
    {
        $species = in_array($request->query('especie'), ['perro', 'gato'], true) ? $request->query('especie') : 'perro';
        $size = array_key_exists($request->query('tamano'), (array) config('tools.dog_sizes')) ? $request->query('tamano') : 'mediano';
        $yearsRaw = $request->query('anios');
        $monthsRaw = $request->query('meses');

        $result = null;
        $error = null;
        if ($yearsRaw !== '' || $monthsRaw !== '') {
            $years = $this->number($yearsRaw === '' ? '0' : $yearsRaw, 0, 30);
            $months = $this->number($monthsRaw === '' ? '0' : $monthsRaw, 0, 11);
            if ($years === null || $months === null) {
                $error = 'Ingresa los años (0 a 30) y los meses (0 a 11) con números.';
            } elseif ($years + $months === 0.0) {
                $error = 'Ingresa una edad mayor que cero.';
            } else {
                $total = $years + $months / 12;
                $result = ['pet' => $total, 'human' => PetTools::humanAge($species, $total, $size)];
            }
        }

        return $this->view('tools/age', [
            'title'   => 'Calculadora de edad',
            'species' => $species,
            'size'    => $size,
            'years'   => $yearsRaw,
            'months'  => $monthsRaw,
            'result'  => $result,
            'error'   => $error,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function toxicity(Request $request, array $params): Response
    {
        $pet = $request->query('mascota');
        $food = $request->query('alimento');
        $result = ($pet !== '' && $food !== '') ? PetTools::toxicity($pet, $food) : null;

        return $this->view('tools/toxicity', [
            'title'  => 'Alimentos peligrosos',
            'pet'    => isset(pets()[$pet]) ? $pet : '',
            'food'   => array_key_exists($food, (array) config('tools.foods')) ? $food : '',
            'result' => $result,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function weight(Request $request, array $params): Response
    {
        $species = in_array($request->query('especie'), ['perro', 'gato'], true) ? $request->query('especie') : 'perro';
        $conditions = (array) config('tools.body_conditions');
        $condition = (int) $request->query('condicion');
        $weightRaw = $request->query('peso');

        $result = null;
        $error = null;
        if ($weightRaw !== '' || $request->query('condicion') !== '') {
            $weight = $this->number($weightRaw, 0.1, 120);
            if ($weight === null) {
                $error = 'Ingresa el peso actual en kilos, entre 0.1 y 120 (usa punto decimal).';
            } elseif (!isset($conditions[$condition])) {
                $error = 'Elige la descripción que mejor corresponde a tu mascota.';
            } else {
                $result = PetTools::healthyWeight($weight, $condition) + ['weight' => $weight];
            }
        }

        return $this->view('tools/weight', [
            'title'      => 'Calculadora de peso saludable',
            'species'    => $species,
            'weight'     => $weightRaw,
            'condition'  => isset($conditions[$condition]) ? $condition : 0,
            'conditions' => $conditions,
            'result'     => $result,
            'error'      => $error,
        ]);
    }

    private function number(string $value, float $min, float $max): ?float
    {
        $value = str_replace(',', '.', trim($value));
        if (preg_match('/^\d{1,3}(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }
        $number = (float) $value;

        return ($number >= $min && $number <= $max) ? $number : null;
    }
}
