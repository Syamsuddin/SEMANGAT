<?php

namespace App\Modules\Integrasi\Enums;

enum SyncStatus: string
{
    case Running = 'running';
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return __('integrasi.sync_status.'.$this->value);
    }

    /** Varian x-badge (docs/26). */
    public function badge(): string
    {
        return match ($this) {
            self::Running => 'info',
            self::Success => 'success',
            self::Partial => 'warning',
            self::Failed => 'danger',
        };
    }
}
