<?php

namespace App\Modules\Kompetensi\Services;

use App\Modules\Kompetensi\Data\GapLine;
use App\Modules\Kompetensi\Models\Assessment;
use App\Support\Enums\Confidence;
use Illuminate\Support\Carbon;

/**
 * Mesin kesenjangan (KOM-03/04/05, docs/06 § 3) — kalkulasi murni, tanpa I/O.
 * Level aktual = hasil terbaik: keyakinan verified > provisional > indicative, lalu tanggal terbaru,
 * dan belum kedaluwarsa. Asesmen mandiri menunggu/ditolak tidak dipakai. Tanpa data → gap = required, indikatif.
 */
class GapCalculator
{
    /**
     * @param  array<int, int>  $required  competency_id => level dipersyaratkan SKJ
     * @param  iterable<Assessment>  $assessments  hasil penilaian pegawai (kolom non-🔒 cukup)
     * @return list<GapLine> satu baris per butir SKJ, urut gap terbesar
     */
    public function calculate(array $required, iterable $assessments, ?Carbon $on = null): array
    {
        $on ??= today();
        $best = [];

        foreach ($assessments as $assessment) {
            if (! isset($required[$assessment->competency_id]) || ! $this->isUsable($assessment, $on)) {
                continue;
            }
            $current = $best[$assessment->competency_id] ?? null;
            if ($current === null || $this->outranks($assessment, $current)) {
                $best[$assessment->competency_id] = $assessment;
            }
        }

        $lines = [];
        foreach ($required as $competencyId => $requiredLevel) {
            $source = $best[$competencyId] ?? null;
            $lines[] = $source === null
                ? new GapLine($competencyId, $requiredLevel, null, $requiredLevel, Confidence::Indicative, null)
                : new GapLine($competencyId, $requiredLevel, $source->level, max(0, $requiredLevel - $source->level), $source->method->confidence(), $source->id);
        }

        usort($lines, fn (GapLine $a, GapLine $b) => [$b->gap, $a->competencyId] <=> [$a->gap, $b->competencyId]);

        return $lines;
    }

    public function isUsable(Assessment $assessment, Carbon $on): bool
    {
        if ($assessment->rejected_at !== null) {
            return false;
        }
        if ($assessment->isSelfAssessment() && $assessment->validated_at === null) {
            return false;
        }

        return ! $assessment->isExpired($on);
    }

    private function outranks(Assessment $candidate, Assessment $current): bool
    {
        return [$candidate->method->confidence()->rank(), $candidate->assessed_on->toDateString(), $candidate->id]
            > [$current->method->confidence()->rank(), $current->assessed_on->toDateString(), $current->id];
    }
}
