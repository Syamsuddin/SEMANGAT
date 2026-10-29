<?php

namespace App\Modules\Integrasi\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('integrasi.employee_status.'.$this->value);
    }
}
