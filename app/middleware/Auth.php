<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth as AuthService;

/**
 * Protección de rutas por estado de sesión y rol.
 *  - auth:  requiere sesión iniciada.
 *  - admin: requiere rol administrador (403 si es cliente).
 *  - guest: solo visitantes sin sesión (login, registro).
 */
final class Auth
{
    public static function handle(string $name, Request $request): ?Response
    {
        switch ($name) {
            case 'auth':
                return AuthService::check() ? null : self::toLogin($request);

            case 'admin':
                if (!AuthService::check()) {
                    return self::toLogin($request);
                }
                if (!AuthService::isAdmin()) {
                    throw new HttpException(403);
                }

                return null;

            case 'guest':
                return AuthService::check() ? Response::redirect(url('/cuenta')) : null;

            default:
                throw new \LogicException('Middleware desconocido: ' . $name);
        }
    }

    private static function toLogin(Request $request): Response
    {
        if ($request->wantsJson() || $request->isFetch()) {
            return Response::json(['ok' => false, 'message' => 'Inicia sesión para continuar.'], 401);
        }

        if ($request->method === 'GET') {
            $query = $request->allQuery();
            $intended = $request->path . ($query !== [] ? '?' . http_build_query($query) : '');
            Session::set('intended', $intended);
        }
        Session::flash('info', 'Inicia sesión para continuar.');

        return Response::redirect(url('/ingresar'));
    }
}
