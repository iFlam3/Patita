<?php

declare(strict_types=1);

namespace App\Services\Import;

use RuntimeException;

/**
 * Error recuperable de importación (una URL o una fila). El proceso continúa
 * con el siguiente elemento.
 */
class ImportException extends RuntimeException
{
}
