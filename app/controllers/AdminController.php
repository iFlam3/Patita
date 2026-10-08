<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Import\ImportReport;
use App\Services\Import\Sources;
use App\Services\Mailer;
use Throwable;

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

    /**
     * Envía un correo de prueba al administrador actual (útil en hostings sin
     * terminal). Nunca muestra la contraseña SMTP.
     *
     * @param array<string, string> $params
     */
    public function testMail(Request $request, array $params): Response
    {
        $user = $this->user();
        try {
            Mailer::send(
                (string) $user['email'],
                (string) $user['name'],
                'Prueba de correo de ' . config('app.name'),
                'Este es un correo de prueba enviado desde el panel de ' . config('app.name') . ".\n\n"
                . "Si lo recibiste, el envío de correos funciona.\n"
            );
            Session::flash('success', config('app.mail.transport') === 'smtp'
                ? 'Enviamos un correo de prueba a ' . $user['email'] . '. Revisa tu bandeja de entrada y la carpeta de spam.'
                : 'MAIL_TRANSPORT es "log": el correo se guardó en storage/mail en lugar de enviarse.');
        } catch (Throwable $e) {
            log_message('mail', 'Fallo del correo de prueba.', ['error' => $e->getMessage()]);
            Session::flash('error', 'No se pudo enviar el correo de prueba: ' . $e->getMessage());
        }

        return $this->redirect('/admin');
    }
}
