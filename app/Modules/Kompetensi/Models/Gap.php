<?php

namespace App\Modules\Kompetensi\Models;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Database\Factories\GapFactory;
use App\Support\Enums\Confidence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Kesenjangan termaterialisasi (keputusan A4) — Sensitivitas Tinggi, dijaga lewat scope (bukan enkripsi).
 * Ditulis hanya oleh Actions\RecalculateGaps.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $competency_id
 * @property int $required_level
 * @property int|null $actual_level
 * @property int $gap
 * @property Confidence $confidence
 * @property int|null $assessment_id
 * @property Carbon $calculated_at
 * @property-read Employee $employee
 * @property-read Competency $competency
 */
class Gap extends Model
{
    /** @use HasFactory<GapFactory> */
    use HasFactory;

    protected $table = 'cmp_gaps';

    protected $fillable = [
        'employee_id', 'competency_id', 'required_level', 'actual_level', 'gap', 'confidence', 'assessment_id', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'required_level' => 'integer',
            'actual_level' => 'integer',
            'gap' => 'integer',
            'confidence' => Confidence::class,
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Gap>  $query
     * @return Builder<Gap>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('cmp_gaps.employee_id', Employee::query()->visibleTo($user)->select('hr_employees.id'));
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Competency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    protected static function newFactory(): GapFactory
    {
        return GapFactory::new();
    }
}
