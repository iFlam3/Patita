<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validación del lado del servidor con mensajes en español.
 * Solo registra el primer error de cada campo.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, string> $data Valores ya normalizados como cadenas.
     */
    public function __construct(private array $data)
    {
    }

    public function value(string $field): string
    {
        return $this->data[$field] ?? '';
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->addError($field, sprintf('Ingresa %s.', $label));
        }

        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->value($field);
        if ($value !== '' && (filter_var($value, FILTER_VALIDATE_EMAIL) === false || strlen($value) > 190)) {
            $this->addError($field, 'Ingresa un correo válido, por ejemplo nombre@dominio.com.');
        }

        return $this;
    }

    public function length(string $field, string $label, int $min, int $max): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }

        $length = mb_strlen($value, 'UTF-8');
        if ($length < $min) {
            $this->addError($field, sprintf('%s debe tener al menos %d caracteres.', $label, $min));
        } elseif ($length > $max) {
            $this->addError($field, sprintf('%s admite como máximo %d caracteres.', $label, $max));
        }

        return $this;
    }

    /**
     * @param list<string> $allowed
     */
    public function in(string $field, array $allowed, string $message): self
    {
        if (!in_array($this->value($field), $allowed, true)) {
            $this->addError($field, $message);
        }

        return $this;
    }

    public function decimal(string $field, string $label, float $min, float $max): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }

        if (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value) !== 1) {
            $this->addError($field, sprintf('%s debe ser un número con hasta dos decimales (usa punto decimal).', $label));
        } elseif ((float) $value < $min || (float) $value > $max) {
            $this->addError($field, sprintf('%s debe estar entre %s y %s.', $label, (string) $min, (string) $max));
        }

        return $this;
    }

    public function integer(string $field, string $label, int $min, int $max): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }

        if (preg_match('/^\d{1,9}$/', $value) !== 1 || (int) $value < $min || (int) $value > $max) {
            $this->addError($field, sprintf('%s debe ser un número entero entre %d y %d.', $label, $min, $max));
        }

        return $this;
    }

    public function phone(string $field): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (preg_match('/^[0-9+()\-.\s]{7,25}$/', $value) !== 1 || strlen($digits) < 7 || strlen($digits) > 15) {
            $this->addError($field, 'Ingresa un teléfono con 7 a 15 dígitos. Puedes incluir +, espacios, guiones o paréntesis.');
        }

        return $this;
    }

    public function matches(string $field, string $other, string $message): self
    {
        if ($this->value($field) !== $this->value($other)) {
            $this->addError($field, $message);
        }

        return $this;
    }

    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
