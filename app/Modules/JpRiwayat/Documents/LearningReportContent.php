<?php

namespace App\Modules\JpRiwayat\Documents;

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Idp\Models\Idp;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\JpBalance;
use App\Support\Documents\DocumentContent;
use Illuminate\Database\Eloquent\Model;

/**
 * JPR-05: isi Laporan Hasil Pembelajaran — target & realisasi IDP, rekap JP tahun periode, bukti (CatatanJP terverifikasi
 * beserta sertifikat), refleksi pegawai, catatan atasan tentang penerapan di pekerjaan.
 */
class LearningReportContent implements DocumentContent
{
    public function __construct(private readonly JpBalance $balance) {}

    public function type(): DocumentType
    {
        return DocumentType::LearningReport;
    }

    public function view(): string
    {
        return 'jp::pdf.learning-report';
    }

    /** @param  Idp  $documentable */
    public function data(Model $documentable): array
    {
        $documentable->load(['employee.position', 'employee.opd', 'supervisor', 'items.competency', 'items.workResult', 'items.activities', 'checkins']);

        return [
            'idp' => $documentable,
            'progress' => $documentable->checkins->last()->progress ?? [],
            'balance' => $this->balance->forEmployee($documentable->employee_id, $documentable->period_year),
            'records' => JpRecord::query()
                ->where('employee_id', $documentable->employee_id)
                ->where('year', $documentable->period_year)
                ->verified()
                ->with('certificate')
                ->orderBy('activity_start')
                ->get(),
        ];
    }

    public function readyUrl(Model $documentable): string
    {
        return route('jp.overview');
    }
}
