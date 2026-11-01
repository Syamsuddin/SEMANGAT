<?php

namespace App\Modules\Idp\Services;

/**
 * Usulan tautan RHK untuk satu kompetensi (IDP-01) — deterministik, tanpa AI (docs/02 #7):
 * RHK dengan irisan kata terbanyak terhadap nama + deskripsi kompetensi; seri → urutan masukan.
 * Tanpa irisan sama sekali → RHK pertama (usulan umum, wajib ditinjau pegawai).
 */
class WorkResultMatcher
{
    private const STOPWORDS = ['dan', 'yang', 'untuk', 'dengan', 'dalam', 'pada', 'atau', 'dari', 'oleh', 'sesuai', 'serta', 'secara', 'terhadap', 'kepada'];

    /**
     * @param  array<int, string>  $workResults  id => deskripsi RHK, urut kode
     */
    public function suggest(string $competencyText, array $workResults): ?int
    {
        if ($workResults === []) {
            return null;
        }

        $needle = $this->tokens($competencyText);
        $bestId = array_key_first($workResults);
        $bestScore = 0;

        foreach ($workResults as $id => $description) {
            $score = count(array_intersect($needle, $this->tokens($description)));
            if ($score > $bestScore) {
                [$bestId, $bestScore] = [$id, $score];
            }
        }

        return $bestId;
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn (string $w) => mb_strlen($w) >= 4 && ! in_array($w, self::STOPWORDS, true),
        )));
    }
}
