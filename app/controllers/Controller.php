<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\Auth;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    /**
     * Redirige devolviendo los datos del formulario y los errores.
     *
     * @param array<string, mixed>  $input
     * @param array<string, string> $errors
     */
    protected function backWithErrors(string $path, array $input, array $errors, string $summary = 'Revisa los campos marcados.'): Response
    {
        Session::flashInput($input, $errors);
        Session::flash('error', $summary);

        return $this->redirect($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function user(): array
    {
        $user = Auth::user();
        if ($user === null) {
            throw new HttpException(403);
        }

        return $user;
    }

    protected function intParam(string $value): int
    {
        if (preg_match('/^\d{1,10}$/', $value) !== 1) {
            throw new HttpException(404);
        }

        return (int) $value;
    }
}
