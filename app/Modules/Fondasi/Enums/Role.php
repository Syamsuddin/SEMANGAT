<?php

namespace App\Modules\Fondasi\Enums;

/** Sembilan peran docs/05_USER_ROLE.md. */
enum Role: string
{
    case Asn = 'asn';
    case Atasan = 'atasan';
    case KoordinatorOpd = 'koordinator_opd';
    case Fasilitator = 'fasilitator';
    case Pengelola = 'pengelola';
    case Clo = 'clo';
    case DewanPengarah = 'dewan_pengarah';
    case AdminTeknis = 'admin_teknis';
    case Auditor = 'auditor';

    public function label(): string
    {
        return __('fondasi.roles.'.$this->value);
    }

    /** Peran turunan otomatis — tidak boleh ditugaskan manual (FON-03, docs/20 #3). */
    public function isDerived(): bool
    {
        return in_array($this, [self::Asn, self::Atasan], true);
    }

    /** FON-06: 2FA wajib. */
    public function requiresTwoFactor(): bool
    {
        return in_array($this, [self::Pengelola, self::Clo, self::AdminTeknis, self::Auditor], true);
    }

    /** Penugasan bercakupan OPD. */
    public function requiresOpdScope(): bool
    {
        return $this === self::KoordinatorOpd;
    }

    /**
     * Urutan peran utama untuk menentukan beranda (FON-01): yang lebih awal menang.
     *
     * @return list<self>
     */
    public static function primaryOrder(): array
    {
        return [
            self::Pengelola, self::AdminTeknis, self::Auditor, self::Clo, self::DewanPengarah,
            self::KoordinatorOpd, self::Fasilitator, self::Atasan, self::Asn,
        ];
    }

    /** @return list<self> */
    public static function assignable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => ! $r->isDerived()));
    }
}
