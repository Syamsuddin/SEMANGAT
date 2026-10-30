<?php

namespace App\Modules\Integrasi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rencana Hasil Kerja (RHK) ⇅ — BACA SAJA (INT-02).
 *
 * @property int $id
 * @property int $skp_summary_id
 * @property string $code
 * @property string $description
 * @property string|null $special_expectation
 * @property string|null $realization
 * @property Carbon $synced_at
 */
class SkpWorkResult extends Model
{
    protected $table = 'hr_skp_work_results';

    protected $fillable = ['skp_summary_id', 'code', 'description', 'special_expectation', 'realization', 'source', 'external_id', 'synced_at'];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    /** @return BelongsTo<SkpSummary, $this> */
    public function summary(): BelongsTo
    {
        return $this->belongsTo(SkpSummary::class, 'skp_summary_id');
    }
}
