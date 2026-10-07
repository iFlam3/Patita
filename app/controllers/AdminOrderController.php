<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Order;
use RuntimeException;

final class AdminOrderController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        $status = $request->query('estado');
        if (!array_key_exists($status, (array) config('catalog.order_statuses'))) {
            $status = '';
        }

        return $this->view('admin/orders/index', [
            'title'  => 'Pedidos',
            'status' => $status,
            'counts' => Order::countsByStatus(),
            'result' => Order::adminList($status, max(1, (int) $request->query('pagina', '1'))),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): Response
    {
        $order = $this->findOrFail($params);

        return $this->view('admin/orders/show', [
            'title'       => 'Pedido ' . order_number((int) $order['id']),
            'order'       => $order,
            'items'       => Order::items((int) $order['id']),
            'address'     => Order::address($order),
            'transitions' => Order::allowedTransitions((string) $order['status']),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function updateStatus(Request $request, array $params): Response
    {
        $order = $this->findOrFail($params);
        $status = $request->input('status');

        try {
            Order::updateStatus((int) $order['id'], $status);
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $this->redirect('/admin/pedidos/' . (int) $order['id']);
        }

        $message = 'El pedido pasó a "' . order_status_label($status) . '".';
        if ($status === 'cancelled') {
            $message .= ' Devolvimos al stock las unidades de los productos que siguen existiendo.';
        }
        Session::flash('success', $message);

        return $this->redirect('/admin/pedidos/' . (int) $order['id']);
    }

    /**
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function findOrFail(array $params): array
    {
        $order = Order::find($this->intParam($params['id'] ?? ''));
        if ($order === null) {
            throw new HttpException(404, 'Ese pedido no existe.');
        }

        return $order;
    }
}
