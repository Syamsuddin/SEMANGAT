<?php

namespace App\Modules\Integrasi\Models;

use App\Modules\Fondasi\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * SKPRingkas ⇅ — BACA SAJA (INT-02). Ditulis hanya oleh adaptor M02; tidak ada nilai/predikat (docs/02).
 *
 * @property int $id
 * @property int $employee_id
 * @property int $period_year
 * @property string|null $dialogue_notes
 * @property string|null $realization_notes
 * @property string $source
 * @property Carbon $synced_at
 * @property-read Collection<int, SkpWorkResult> $workResults
 */
class SkpSummary extends Model
{
    protected $table = 'hr_skp_summaries';

    protected $fillable = ['employee_id', 'period_year', 'dialogue_notes', 'realization_notes', 'source', 'external_id', 'synced_at'];

    protected function casts(): array
    {
        return ['period_year' => 'integer', 'synced_at' => 'datetime'];
    }

    /**
     * @param  Builder<SkpSummary>  $query
     * @return Builder<SkpSummary>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('hr_skp_summaries.employee_id', Employee::query()->visibleTo($user)->select('hr_employees.id'));
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<SkpWorkResult, $this> */
    public function workResults(): HasMany
    {
        return $this->hasMany(SkpWorkResult::class);
    }
}
