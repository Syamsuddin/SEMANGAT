<?php

namespace App\Modules\JpRiwayat\Actions;

use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\JpRiwayat\Enums\JpMode;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * JPR-03: titik masuk pencatatan otomatis dari M08 (pembelajaran tempat kerja disetujui), M09 (kelas selesai),
 * M10 (sesi coaching) → CatatanJP `verified` tanpa verifikasi manual (docs/06 § 6.1).
 * Idempoten per sumber: memanggil ulang untuk sumber yang sama mengembalikan catatan yang ada.
 */
class RecordJp
{
    public function automatic(int $employeeId, Model $source, string $title, ActivityPortion $portion, JpMode $mode, float $jp, Carbon $start, Carbon $end): JpRecord
    {
        if ($jp <= 0 || $end->lt($start)) {
            throw new InvalidArgumentException('JP harus > 0 dan tanggal selesai tidak sebelum tanggal mulai.');
        }

        return JpRecord::query()->firstOrCreate(
            ['sourceable_type' => $source->getMorphClass(), 'sourceable_id' => $source->getKey()],
            [
                'employee_id' => $employeeId,
                'year' => $end->year,
                'title' => $title,
                'portion' => $portion,
                'mode' => $mode,
                'jp' => round($jp, 1),
                'activity_start' => $start->toDateString(),
                'activity_end' => $end->toDateString(),
                'status' => JpStatus::Verified,
                'verified_at' => now(),
            ],
        );
    }
}
