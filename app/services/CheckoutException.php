<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Error de negocio al confirmar un pedido (stock, precios o carrito vacío).
 * El mensaje y los detalles se muestran al cliente.
 */
final class CheckoutException extends RuntimeException
{
    /**
     * @param list<string> $details
     */
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    /**
     * @return list<string>
     */
    public function details(): array
    {
        return $this->details;
    }
}
