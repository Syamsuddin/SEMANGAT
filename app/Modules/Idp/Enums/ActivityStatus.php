<?php

namespace App\Modules\Idp\Enums;

enum ActivityStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Dropped = 'dropped';

    public function label(): string
    {
        return __('idp.activity_status.'.$this->value);
    }
}
