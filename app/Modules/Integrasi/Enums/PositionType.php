<?php

namespace App\Modules\Integrasi\Enums;

enum PositionType: string
{
    case Structural = 'structural';
    case Functional = 'functional';
    case Executor = 'executor';

    public function label(): string
    {
        return __('integrasi.position_type.'.$this->value);
    }
}
