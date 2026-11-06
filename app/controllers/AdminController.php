<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Mailer;
use App\Services\SalesForecast;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Inicio del panel (métricas de ventas) y correo de prueba.
 */
final class AdminController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function dashboard(Request $request, array $params): Response
    {
        $range = $request->query('rango', '90d');
        if (!isset(self::RANGES[$range])) {
            $range = '90d';
        }

        return $this->view('admin/dashboard', [
            'title'        => 'Panel de administración',
            'range'        => $range,
            'sales'        => self::sales($range),
            'stats'        => Product::stats(),
            'orderCounts'  => Order::countsByStatus(),
            'recentOrders' => Order::adminList('', 1, 5)['items'],
            'userCount'    => User::count(),
        ]);
    }

    /** Rango => [unidad, periodos completos, etiqueta]. El periodo en curso no entra en la regresión. */
    public const RANGES = [
        '30d' => ['day', 30, 'Últimos 30 días'],
        '90d' => ['week', 13, 'Últimas 13 semanas'],
        '12m' => ['month', 12, 'Últimos 12 meses'],
    ];

    private const FORECAST_PERIODS = 3;

    /**
     * Serie de ingresos, comparación con el rango anterior, regresión lineal y proyección.
     *
     * @return array<string, mixed>
     */
    private static function sales(string $range): array
    {
        [$unit, $periods] = self::RANGES[$range];
        $utc = new DateTimeZone('UTC');
        $today = new DateTimeImmutable('today', $utc);
        $end = match ($unit) {
            'day'   => $today,
            'week'  => $today->modify('monday this week'),
            'month' => $today->modify('first day of this month'),
        };
        $step = '+1 ' . $unit;
        $start = $end->modify('-' . $periods . ' ' . $unit);
        $prevStart = $start->modify('-' . $periods . ' ' . $unit);
        $fmt = static fn (DateTimeImmutable $d): string => $d->format('Y-m-d H:i:s');

        $raw = Order::revenueSeries($unit, $fmt($start), $fmt($end));
        $labels = [];
        $values = [];
        $orders = 0;
        for ($d = $start; $d < $end; $d = $d->modify($step)) {
            $labels[] = self::periodLabel($d, $unit);
            $values[] = round($raw[$d->format('Y-m-d')]['revenue'] ?? 0.0, 2);
            $orders += $raw[$d->format('Y-m-d')]['orders'] ?? 0;
        }
        // Los periodos anteriores a la primera venta no son "ventas en cero": la tienda aún no vendía.
        while (count($values) > 2 && $values[0] == 0.0) {
            array_shift($values);
            array_shift($labels);
        }
        $futureLabels = [];
        for ($i = 0, $d = $end; $i < self::FORECAST_PERIODS; $i++, $d = $d->modify($step)) {
            $futureLabels[] = self::periodLabel($d, $unit);
        }

        $model = SalesForecast::fit($values);
        $revenue = array_sum($values);
        $previous = Order::salesTotals($fmt($prevStart), $fmt($start))['revenue'];

        return [
            'unit'         => $unit,
            'labels'       => $labels,
            'values'       => $values,
            'fitted'       => SalesForecast::project($model, 0, count($values)),
            'futureLabels' => $futureLabels,
            'forecast'     => SalesForecast::project($model, count($values), self::FORECAST_PERIODS),
            'model'        => $model,
            'revenue'      => $revenue,
            'orders'       => $orders,
            'avgTicket'    => $orders > 0 ? $revenue / $orders : 0.0,
            'change'       => $previous > 0 ? ($revenue - $previous) / $previous * 100 : null,
            'topProducts'  => Order::topProducts($fmt($start), $fmt($end)),
            'byPet'        => Order::revenueByPet($fmt($start), $fmt($end)),
        ];
    }

    private static function periodLabel(DateTimeImmutable $d, string $unit): string
    {
        $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        return $unit === 'month'
            ? $months[(int) $d->format('n') - 1] . ' ' . $d->format('y')
            : $d->format('d/m');
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
