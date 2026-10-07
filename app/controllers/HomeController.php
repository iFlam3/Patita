<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Product;

final class HomeController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        return $this->view('home', [
            'title'       => null,
            'description' => 'Alimento, accesorios e insumos para perros, gatos, aves, peces, reptiles, caballos y animales pequeños.',
            'petCounts'   => Product::countsByPet(),
            'featured'    => Product::featured(8),
            'brands'      => Product::brandSummary(),
        ]);
    }

    /**
     * Página informativa de envíos y pagos (valores tomados de la configuración).
     *
     * @param array<string, string> $params
     */
    public function shipping(Request $request, array $params): Response
    {
        return $this->view('pages/shipping', [
            'title'          => 'Envíos y pagos',
            'flatRate'       => to_cents((string) config('app.shop.shipping_flat_rate', '0')),
            'freeFrom'       => \App\Services\Cart::freeShippingFrom(),
            'countries'      => (array) config('app.shop.shipping_countries', []),
            'paymentMethods' => enabled_payment_methods(),
        ]);
    }
}
