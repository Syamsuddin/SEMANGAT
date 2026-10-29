<?php

namespace App\Modules\Integrasi\Data;

final readonly class ValidationResult
{
    /**
     * @param  list<EmployeeRecord>  $valid
     * @param  list<RowError>  $errors
     */
    public function __construct(public array $valid, public array $errors) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
