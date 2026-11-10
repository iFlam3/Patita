<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\PagoMovil;
use Throwable;

/**
 * Datos de Pago Móvil editables por el administrador.
 */
final class AdminPaymentController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params): Response
    {
        $values = PagoMovil::get();
        $errors = Session::errors();
        if ($errors !== []) {
            foreach ($values as $key => $value) {
                $values[$key] = old($key, $value);
            }
        }

        return $this->view('admin/payment', [
            'title'  => 'Pago Móvil',
            'values' => $values,
            'errors' => $errors,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): Response
    {
        $input = [];
        foreach (array_keys(PagoMovil::DEFAULTS) as $field) {
            $input[$field] = trim($request->input($field));
        }

        $v = (new Validator($input))
            ->required('bank', 'el banco')->length('bank', 'El banco', 3, 80)
            ->required('holder', 'el titular')->length('holder', 'El titular', 2, 80);
        if (preg_match('/^[VEJGP]-?[\d.]{5,12}$/i', $input['id_doc']) !== 1) {
            $v->addError('id_doc', 'Usa el formato V-12345678 (V, E, J, G o P).');
        }
        if (preg_match('/^0\d{3}-?\d{7}$/', $input['phone']) !== 1) {
            $v->addError('phone', 'Usa el formato 0412-1234567.');
        }
        if ($v->fails()) {
            return $this->backWithErrors('/admin/pago-movil', $input, $v->errors());
        }

        $input['id_doc'] = strtoupper($input['id_doc']);
        try {
            PagoMovil::save($input);
            Session::flash('success', 'Datos de Pago Móvil actualizados.');
        } catch (Throwable $e) {
            log_message('app', 'Fallo al guardar Pago Móvil.', ['error' => $e->getMessage()]);
            Session::flash('error', $e->getMessage());
        }

        return $this->redirect('/admin/pago-movil');
    }
}
