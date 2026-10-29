<?php

namespace App\Modules\Integrasi\Data;

final readonly class RowError
{
    /** @param list<string> $messages */
    public function __construct(public EmployeeRecord $record, public array $messages) {}

    public function message(): string
    {
        return implode('; ', $this->messages);
    }
}
