<?php

namespace App\Modules\Idp\Enums;

/** Status IDP (docs/06 § 4): draft → submitted → approved → active → closed; returned kembali ke draft; cancelled oleh pengelola. */
enum IdpStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Active = 'active';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('idp.status.'.$this->value);
    }

    /** Varian x-badge (docs/26). */
    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Submitted => 'info',
            self::Approved, self::Active => 'success',
            self::Closed => 'neutral',
            self::Cancelled => 'danger',
        };
    }
}
