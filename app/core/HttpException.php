<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Error HTTP controlado (404, 403, 405, 419, 429...). El Kernel lo convierte en
 * una página de error en español sin exponer detalles internos.
 */
final class HttpException extends RuntimeException
{
    public function __construct(private int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            403 => 'No tienes permiso para ver esta página.',
            404 => 'No encontramos la página que buscas.',
            405 => 'Esta acción no está permitida.',
            419 => 'La página expiró. Vuelve a intentarlo.',
            429 => 'Demasiadas solicitudes. Espera un momento.',
            default => 'Ocurrió un error inesperado.',
        };
    }
}
