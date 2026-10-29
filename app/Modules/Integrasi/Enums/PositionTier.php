<?php

namespace App\Modules\Integrasi\Enums;

/** Jenjang untuk template ekspektasi "Kompeten" (docs/07 § hr_positions.tier). */
enum PositionTier: string
{
    case Pelaksana = 'pelaksana';
    case Fungsional = 'fungsional';
    case Pengawas = 'pengawas';
    case AdministratorJpt = 'administrator_jpt';
}
