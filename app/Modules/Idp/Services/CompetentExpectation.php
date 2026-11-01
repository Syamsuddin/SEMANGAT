<?php

namespace App\Modules\Idp\Services;

use App\Modules\Integrasi\Enums\PositionTier;

/**
 * IDP-05: teks usulan ekspektasi khusus perilaku "Kompeten" per jenjang jabatan (KEBUTUHAN.md Lampiran C).
 * Ekspektasi tetap ditetapkan pejabat penilai; teks dapat disunting pegawai sebelum disalin ke e-Kinerja.
 * Corpu tidak menulis apa pun ke e-Kinerja (docs/02 #2).
 */
class CompetentExpectation
{
    public function for(?PositionTier $tier): string
    {
        return __('idp.expectation.'.($tier ?? PositionTier::Pelaksana)->value);
    }
}
