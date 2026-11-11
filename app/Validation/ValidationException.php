<?php
declare(strict_types=1);

namespace NVRO\Validation;

final class ValidationException extends \RuntimeException
{
    public array $errors;

    public function __construct(array $errors)
    {
        parent::__construct(reset($errors) ?: 'Please check your information.');
        $this->errors = $errors;
    }
}
