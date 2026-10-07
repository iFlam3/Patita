<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Import\ImportReport;
use App\Services\Import\Sources;

/**
 * Inicio del panel e información del importador (que se ejecuta por CLI).
 */
final class AdminController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function dashboard(Request $request, array $params): Response
    {
        $reports = ImportReport::latest(1);

        return $this->view('admin/dashboard', [
            'title'        => 'Panel de administración',
            'stats'        => Product::stats(),
            'orderCounts'  => Order::countsByStatus(),
            'recentOrders' => Order::adminList('', 1, 5)['items'],
            'userCount'    => User::count(),
            'lastImport'   => $reports[0] ?? null,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function import(Request $request, array $params): Response
    {
        return $this->view('admin/import', [
            'title'          => 'Importador de productos',
            'sources'        => Sources::all(),
            'allowedSources' => (array) config('app.import.allowed_sources', []),
            'reports'        => ImportReport::latest(10),
            'importConfig'   => (array) config('app.import'),
        ]);
    }
}
