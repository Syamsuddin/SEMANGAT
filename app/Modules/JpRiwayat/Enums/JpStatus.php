<?php

namespace App\Modules\JpRiwayat\Enums;

/** out_jp_records.status — hanya `verified` dihitung ke saldo 20 JP (docs/06 § 6). */
enum JpStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('jp.status.'.$this->value);
    }

    /** Varian x-badge (docs/26). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
