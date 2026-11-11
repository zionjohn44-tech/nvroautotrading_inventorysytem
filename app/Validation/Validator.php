<?php
declare(strict_types=1);

namespace NVRO\Validation;

final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data) { $this->data = $data; }

    public function text(string $key, string $label, int $max = 255, bool $required = true): string
    {
        $value = is_scalar($this->data[$key] ?? null) ? trim((string) $this->data[$key]) : '';
        if ($required && $value === '') {
            $this->errors[$key] = "$label is required.";
        } elseif (mb_strlen($value) > $max) {
            $this->errors[$key] = "$label must be $max characters or fewer.";
        }
        return $value;
    }

    public function choice(string $key, string $label, array $options): string
    {
        $value = $this->text($key, $label);
        if (!in_array($value, $options, true)) {
            $this->errors[$key] = "Select a valid $label.";
        }
        return $value;
    }

    public function integer(string $key, string $label, int $min = 1, int $max = PHP_INT_MAX): int
    {
        $value = $this->text($key, $label);
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min || (int) $value > $max) {
            $this->errors[$key] = "$label must be a whole number between $min and $max.";
        }
        return (int) $value;
    }

    public function price(string $key, string $label): string
    {
        $value = $this->text($key, $label);
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value) || (float) $value <= 0) {
            $this->errors[$key] = "$label must be a positive amount with up to two decimal places.";
        }
        return $value;
    }

    public function phone(string $key = 'Contact_Number'): string
    {
        $value = $this->text($key, 'Contact number', 15);
        if (!preg_match('/^\+?[0-9 ()-]{7,15}$/', $value) || strlen(preg_replace('/\D/', '', $value)) < 7) {
            $this->errors[$key] = 'Enter a valid contact number (7–15 characters).';
        }
        return $value;
    }

    public function error(string $key, string $message): void { $this->errors[$key] = $message; }

    public function finish(): void
    {
        if ($this->errors) { throw new ValidationException($this->errors); }
    }
}
