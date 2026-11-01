<?php

namespace App\Modules\JpRiwayat\Enums;

/** out_jp_records.mode — bentuk bangkom klasikal / nonklasikal. */
enum JpMode: string
{
    case Classical = 'classical';
    case NonClassical = 'non_classical';

    public function label(): string
    {
        return __('jp.mode.'.$this->value);
    }
}
