<?php

namespace App\Modules\Idp\Enums;

/** Porsi model 10:20:70 — pelatihan terstruktur · belajar sosial · belajar di tempat kerja. */
enum ActivityPortion: int
{
    case Formal = 10;
    case Social = 20;
    case Workplace = 70;

    public function label(): string
    {
        return __('idp.portion.'.$this->value);
    }
}
