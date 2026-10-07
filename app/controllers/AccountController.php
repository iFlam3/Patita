<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Order;
use App\Models\User;
use App\Services\Auth;

final class AccountController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): Response
    {
        $user = $this->user();

        return $this->view('account/index', [
            'title'  => 'Mi cuenta',
            'user'   => $user,
            'orders' => Order::forUser((int) $user['id'], 5),
            'errors' => Session::errors(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function updateProfile(Request $request, array $params): Response
    {
        $user = $this->user();
        $input = [
            'name'             => $request->input('name'),
            'email'            => User::normalizeEmail($request->input('email')),
            'confirm_password' => $request->raw('confirm_password'),
        ];

        $v = new Validator($input);
        $v->required('name', 'tu nombre')->length('name', 'El nombre', 2, 120)
            ->required('email', 'tu correo')->email('email');

        $emailChanged = $input['email'] !== (string) $user['email'];
        if ($emailChanged) {
            if ($input['confirm_password'] === '') {
                $v->addError('confirm_password', 'Para cambiar tu correo, confirma tu contraseña actual.');
            } elseif (!password_verify($input['confirm_password'], (string) $user['password_hash'])) {
                $v->addError('confirm_password', 'La contraseña actual no es correcta.');
            }
            if (!$v->fails() && User::emailExists($input['email'], (int) $user['id'])) {
                $v->addError('email', 'Ese correo ya está registrado en otra cuenta.');
            }
        }

        if ($v->fails()) {
            return $this->backWithErrors('/cuenta#perfil', $input, $v->errors());
        }

        User::updateProfile((int) $user['id'], $input['name'], $input['email']);
        $fresh = User::find((int) $user['id']);
        if ($fresh !== null) {
            Auth::refresh($fresh);
        }
        Session::flash('success', 'Guardamos los cambios de tu perfil.');

        return $this->redirect('/cuenta');
    }

    /**
     * @param array<string, string> $params
     */
    public function updatePassword(Request $request, array $params): Response
    {
        $user = $this->user();
        $input = [
            'current_password'      => $request->raw('current_password'),
            'password'              => $request->raw('password'),
            'password_confirmation' => $request->raw('password_confirmation'),
        ];

        $v = new Validator($input);
        $v->required('current_password', 'tu contraseña actual')
            ->required('password', 'la contraseña nueva')
            ->matches('password_confirmation', 'password', 'Las contraseñas nuevas no coinciden.');
        if ($input['current_password'] !== '' && !password_verify($input['current_password'], (string) $user['password_hash'])) {
            $v->addError('current_password', 'La contraseña actual no es correcta.');
        }
        foreach (AuthController::passwordProblems($input['password'], (string) $user['email']) as $problem) {
            $v->addError('password', $problem);
        }
        if ($input['password'] !== '' && $input['password'] === $input['current_password']) {
            $v->addError('password', 'La contraseña nueva debe ser distinta de la actual.');
        }

        if ($v->fails()) {
            return $this->backWithErrors('/cuenta#contrasena', [], $v->errors());
        }

        User::updatePassword((int) $user['id'], password_hash($input['password'], PASSWORD_DEFAULT));
        $fresh = User::find((int) $user['id']);
        if ($fresh !== null) {
            // Mantiene esta sesión; las demás se cierran al cambiar la huella.
            Auth::refresh($fresh);
        }
        Session::flash('success', 'Actualizamos tu contraseña. Cerramos tus otras sesiones abiertas.');

        return $this->redirect('/cuenta');
    }

    /**
     * @param array<string, string> $params
     */
    public function orders(Request $request, array $params): Response
    {
        $user = $this->user();

        return $this->view('account/orders', [
            'title'  => 'Mis pedidos',
            'orders' => Order::forUser((int) $user['id'], 100),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function order(Request $request, array $params): Response
    {
        $user = $this->user();
        $order = Order::findForUser($this->intParam($params['id'] ?? ''), (int) $user['id']);
        if ($order === null) {
            throw new HttpException(404, 'No encontramos ese pedido en tu cuenta.');
        }

        return $this->view('account/order', [
            'title'   => 'Pedido ' . order_number((int) $order['id']),
            'order'   => $order,
            'items'   => Order::items((int) $order['id']),
            'address' => Order::address($order),
        ]);
    }
}
