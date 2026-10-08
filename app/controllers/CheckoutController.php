<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Order;
use App\Services\Cart;
use App\Services\CheckoutException;
use App\Services\CheckoutService;

/**
 * Checkout en tres pasos: datos de entrega → resumen → confirmación.
 * Requiere sesión iniciada (middleware 'auth').
 */
final class CheckoutController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function form(Request $request, array $params): Response
    {
        $summary = Cart::summary();
        if ($summary['lines'] === []) {
            Session::flash('info', 'Tu carrito está vacío. Agrega productos para continuar.');

            return $this->redirect('/carrito');
        }

        return $this->view('checkout/form', [
            'title'          => 'Datos de entrega',
            'summary'        => $summary,
            'values'         => $this->prefill(),
            'countries'      => $this->shippingCountries(),
            'paymentMethods' => enabled_payment_methods(),
        ]);
    }

    /**
     * Valida los datos y los guarda en la sesión para el resumen.
     *
     * @param array<string, string> $params
     */
    public function review(Request $request, array $params): Response
    {
        $fields = ['name', 'email', 'phone', 'country', 'line1', 'line2', 'city', 'region', 'postal_code', 'references', 'payment_method'];
        $input = [];
        foreach ($fields as $field) {
            $input[$field] = $request->input($field);
        }
        $input['email'] = mb_strtolower($input['email'], 'UTF-8');
        $input['country'] = strtoupper($input['country']);

        $v = new Validator($input);
        $v->required('name', 'tu nombre completo')->length('name', 'El nombre', 3, 120)
            ->required('email', 'tu correo')->email('email')
            ->required('phone', 'un teléfono de contacto')->phone('phone')
            ->in('country', array_keys($this->shippingCountries()), 'Elige un país de envío disponible.')
            ->required('line1', 'la calle y el número')->length('line1', 'La dirección', 5, 150)
            ->length('line2', 'El complemento', 0, 100)
            ->required('city', 'la ciudad o municipio')->length('city', 'La ciudad', 2, 80)
            ->length('region', 'El estado o provincia', 0, 80)
            ->length('postal_code', 'El código postal', 0, 12)
            ->length('references', 'Las referencias', 0, 250)
            ->in('payment_method', array_keys(enabled_payment_methods()), 'Elige una forma de pago.');
        if ($input['postal_code'] !== '' && preg_match('/^[A-Za-z0-9\- ]{3,12}$/', $input['postal_code']) !== 1) {
            $v->addError('postal_code', 'El código postal solo admite letras, números, espacios y guiones.');
        }

        if ($v->fails()) {
            return $this->backWithErrors('/checkout', $input, $v->errors());
        }

        Session::set('checkout_data', [
            'name'           => $input['name'],
            'email'          => $input['email'],
            'phone'          => $input['phone'],
            'payment_method' => $input['payment_method'],
            'address'        => [
                'line1'       => $input['line1'],
                'line2'       => $input['line2'],
                'city'        => $input['city'],
                'region'      => $input['region'],
                'postal_code' => $input['postal_code'],
                'country'     => $input['country'],
                'references'  => $input['references'],
            ],
        ]);

        return $this->redirect('/checkout/revisar');
    }

    /**
     * @param array<string, string> $params
     */
    public function showReview(Request $request, array $params): Response
    {
        $data = Session::get('checkout_data');
        if (!is_array($data)) {
            return $this->redirect('/checkout');
        }

        $summary = Cart::summary();
        if ($summary['lines'] === []) {
            Session::flash('info', 'Tu carrito está vacío.');

            return $this->redirect('/carrito');
        }

        $token = Session::get('checkout_token');
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            $token = bin2hex(random_bytes(32));
            Session::set('checkout_token', $token);
        }

        return $this->view('checkout/review', [
            'title'         => 'Revisa tu pedido',
            'summary'       => $summary,
            'data'          => $data,
            'checkoutToken' => $token,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function place(Request $request, array $params): Response
    {
        $user = $this->user();
        $token = $request->input('checkout_token');
        $fingerprint = $request->input('cart_fingerprint');

        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1 || preg_match('/^[a-f0-9]{64}$/', $fingerprint) !== 1) {
            Session::flash('error', 'No pudimos validar el formulario. Revisa el resumen y confirma de nuevo.');

            return $this->redirect('/checkout/revisar');
        }

        // Reenvío o doble clic de un pedido ya creado: mostrar el existente.
        $existing = Order::findByCheckoutToken($token);
        if ($existing !== null && (int) $existing['user_id'] === (int) $user['id']) {
            Session::flash('info', 'Este pedido ya estaba confirmado; no se creó uno nuevo.');

            return $this->redirect('/pedido/' . (int) $existing['id'] . '/confirmacion');
        }

        $sessionToken = Session::get('checkout_token');
        if (!is_string($sessionToken) || !hash_equals($sessionToken, $token)) {
            Session::flash('error', 'El resumen que confirmaste ya no está vigente. Revísalo y confirma de nuevo.');

            return $this->redirect('/checkout/revisar');
        }

        $data = Session::get('checkout_data');
        if (!is_array($data)) {
            return $this->redirect('/checkout');
        }

        try {
            $result = CheckoutService::placeOrder($user, $data, $token, $fingerprint);
        } catch (CheckoutException $e) {
            Session::flash('error', $e->getMessage());
            foreach ($e->details() as $detail) {
                Session::flash('error', $detail);
            }

            return $this->redirect($e->details() !== [] ? '/carrito' : '/checkout/revisar');
        }

        Session::forget('checkout_token');
        if ($result['duplicate']) {
            Session::flash('info', 'Este pedido ya estaba confirmado; no se creó uno nuevo.');
        }

        return $this->redirect('/pedido/' . $result['id'] . '/confirmacion');
    }

    /**
     * @param array<string, string> $params
     */
    public function confirmation(Request $request, array $params): Response
    {
        $user = $this->user();
        $order = Order::findForUser($this->intParam($params['id'] ?? ''), (int) $user['id']);
        if ($order === null) {
            throw new HttpException(404, 'No encontramos ese pedido en tu cuenta.');
        }

        return $this->view('checkout/confirmation', [
            'title'   => 'Pedido ' . order_number((int) $order['id']),
            'order'   => $order,
            'items'   => Order::items((int) $order['id']),
            'address' => Order::address($order),
        ]);
    }

    /**
     * Valores iniciales: lo ingresado en esta sesión, si no el último pedido,
     * y si no los datos de la cuenta.
     *
     * @return array<string, string>
     */
    private function prefill(): array
    {
        $user = $this->user();
        $values = [
            'name' => (string) $user['name'], 'email' => (string) $user['email'], 'phone' => '',
            'country' => (string) config('app.country'), 'line1' => '', 'line2' => '', 'city' => '',
            'region' => '', 'postal_code' => '', 'references' => '',
            'payment_method' => (string) array_key_first(enabled_payment_methods()),
        ];

        $session = Session::get('checkout_data');
        $latest = is_array($session) ? null : Order::latestForUser((int) $user['id']);
        if (is_array($session)) {
            $values = array_merge($values, array_map('strval', array_filter([
                'name' => $session['name'] ?? '', 'email' => $session['email'] ?? '', 'phone' => $session['phone'] ?? '',
                'payment_method' => $session['payment_method'] ?? '',
            ])), array_map('strval', (array) ($session['address'] ?? [])));
        } elseif ($latest !== null) {
            $values = array_merge($values, [
                'name' => (string) $latest['customer_name'], 'phone' => (string) $latest['customer_phone'],
            ], Order::address($latest));
        }

        // Los datos devueltos por un error de validación tienen prioridad.
        foreach (array_keys($values) as $key) {
            $values[$key] = old($key, $values[$key]);
        }

        return $values;
    }

    /**
     * @return array<string, string> código => nombre
     */
    private function shippingCountries(): array
    {
        return shipping_countries();
    }
}
