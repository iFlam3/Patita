<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Cart;

final class CartController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        return $this->view('cart/index', [
            'title'   => 'Carrito',
            'summary' => Cart::summary(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function add(Request $request, array $params): Response
    {
        $productId = $this->positiveInt($request->input('product_id'));
        $quantity = $this->positiveInt($request->input('quantity', '1'));

        $result = ($productId === 0)
            ? ['ok' => false, 'message' => 'Producto no válido.', 'quantity' => 0, 'count' => Cart::count(), 'name' => '']
            : Cart::add($productId, max(1, $quantity));

        if ($request->wantsJson()) {
            return Response::json($result, $result['ok'] ? 200 : 422);
        }

        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect(safe_path($request->input('return_to'), '/carrito'));
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $productId = $this->positiveInt($request->input('product_id'));
        $raw = $request->input('quantity');
        if ($productId === 0 || preg_match('/^\d{1,4}$/', $raw) !== 1) {
            Session::flash('error', 'Ingresa una cantidad válida (número entero).');

            return $this->redirect('/carrito');
        }

        $result = Cart::update($productId, (int) $raw);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/carrito');
    }

    /**
     * @param array<string, string> $params
     */
    public function remove(Request $request, array $params): Response
    {
        $productId = $this->positiveInt($request->input('product_id'));
        if ($productId > 0) {
            Cart::remove($productId);
            Session::flash('success', 'Quitaste el producto del carrito.');
        }

        return $this->redirect('/carrito');
    }

    private function positiveInt(string $value): int
    {
        return preg_match('/^\d{1,9}$/', $value) === 1 ? (int) $value : 0;
    }
}
