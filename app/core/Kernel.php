<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Ciclo de vida de una petición web: cabeceras de seguridad, sesión,
 * enrutamiento y manejo centralizado de errores.
 */
final class Kernel
{
    public static function handle(): void
    {
        $basePath = self::detectBasePath();
        config_set('runtime.base_path', $basePath);

        $request = Request::capture($basePath);
        config_set('runtime.https', $request->isHttps());
        self::sendSecurityHeaders($request);

        try {
            Session::start($request->isHttps(), $basePath === '' ? '/' : $basePath . '/');

            $router = new Router();
            $register = require BASE_PATH . '/app/routes.php';
            $register($router);

            $response = $router->dispatch($request);
        } catch (HttpException $e) {
            $response = self::errorResponse($e->status(), $e->getMessage(), $request);
        } catch (Throwable $e) {
            log_message('app', 'Excepción no controlada: ' . $e->getMessage(), [
                'type' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'path' => $request->path,
            ]);
            $detail = config('app.debug') === true ? get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : '';
            $response = self::errorResponse(500, $detail, $request);
        }

        $response->send();
    }

    private static function detectBasePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        // En Windows dirname('/index.php') devuelve "\": se normaliza otra vez.
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

        return $dir === '.' ? '' : $dir;
    }

    private static function sendSecurityHeaders(Request $request): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cache-Control: no-store, private');
        header(
            "Content-Security-Policy: default-src 'self'; script-src 'self'; "
            . "style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: https:; connect-src 'self'; form-action 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
        );

        if ($request->isHttps() && config('app.env') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function errorResponse(int $status, string $message, Request $request): Response
    {
        $message = $message !== '' ? $message : HttpException::defaultMessage($status);

        if ($request->wantsJson() || $request->isFetch()) {
            return Response::json(['ok' => false, 'message' => $message], $status);
        }

        try {
            $html = View::render('errors/error', [
                'title'   => self::title($status),
                'status'  => $status,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            // Si la plantilla falla (p. ej. base de datos caída) se usa HTML mínimo.
            log_message('app', 'Fallo al renderizar página de error: ' . $e->getMessage());
            $html = '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error ' . $status
                . '</title><body><h1>Error ' . $status . '</h1><p>'
                . e($message) . '</p><p><a href="' . e(url('/')) . '">Volver al inicio</a></p></body></html>';
        }

        return Response::html($html, $status);
    }

    private static function title(int $status): string
    {
        return match ($status) {
            403 => 'Acceso restringido',
            404 => 'Página no encontrada',
            405 => 'Acción no permitida',
            419 => 'La página expiró',
            429 => 'Demasiadas solicitudes',
            default => 'Algo salió mal',
        };
    }
}
