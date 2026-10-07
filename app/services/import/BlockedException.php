<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * La fuente respondió con un bloqueo, CAPTCHA o desafío anti-bot.
 * El importador se detiene por completo: no reintenta ni intenta eludirlo.
 */
final class BlockedException extends ImportException
{
}
