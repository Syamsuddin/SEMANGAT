<?php

namespace App\Modules\Kompetensi\Models;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Database\Factories\AssessmentFactory;
use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Support\Enums\Confidence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * HasilUji — Sensitivitas Tinggi (docs/07, docs/21). Kolom 🔒 notes & report_file_path terenkripsi;
 * pembukaannya hanya lewat Actions\OpenAssessmentReport (audit read_sensitive).
 * Query daftar/mesin kesenjangan memakai kolom non-🔒 saja (lihat NON_SENSITIVE_COLUMNS).
 *
 * @property int $id
 * @property int $employee_id
 * @property int $competency_id
 * @property int $level
 * @property AssessmentMethod $method
 * @property Confidence $confidence
 * @property string|null $institution
 * @property Carbon $assessed_on
 * @property Carbon|null $valid_until
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_note
 * @property string|null $report_file_path
 * @property string|null $notes
 * @property-read Employee $employee
 * @property-read Competency $competency
 * @property-read Employee|null $validator
 */
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    public const NON_SENSITIVE_COLUMNS = [
        'id', 'employee_id', 'competency_id', 'level', 'method', 'confidence', 'institution',
        'assessed_on', 'valid_until', 'validated_by', 'validated_at', 'rejected_at', 'rejection_note', 'created_at',
    ];

    protected $table = 'cmp_assessments';

    protected $fillable = [
        'employee_id', 'competency_id', 'level', 'method', 'confidence', 'institution', 'assessed_on',
        'valid_until', 'validated_by', 'validated_at', 'rejected_at', 'rejection_note', 'report_file_path', 'notes',
    ];

    protected $hidden = ['report_file_path', 'notes'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'method' => AssessmentMethod::class,
            'confidence' => Confidence::class,
            'assessed_on' => 'date',
            'valid_until' => 'date',
            'validated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'report_file_path' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    /**
     * Cakupan baris individu = cakupan pegawai (docs/21 lapis 2).
     *
     * @param  Builder<Assessment>  $query
     * @return Builder<Assessment>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('cmp_assessments.employee_id', Employee::query()->visibleTo($user)->select('hr_employees.id'));
    }

    /**
     * Baris yang boleh dipakai mesin kesenjangan: bukan asesmen mandiri menunggu/ditolak, belum kedaluwarsa.
     *
     * @param  Builder<Assessment>  $query
     * @return Builder<Assessment>
     */
    public function scopeUsable(Builder $query, ?Carbon $on = null): Builder
    {
        $on ??= today();

        return $query
            ->whereNull('rejected_at')
            ->where(fn (Builder $q) => $q->where('method', '!=', AssessmentMethod::SelfAssessment->value)->orWhereNotNull('validated_at'))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $on->toDateString()));
    }

    /**
     * @param  Builder<Assessment>  $query
     * @return Builder<Assessment>
     */
    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('method', AssessmentMethod::SelfAssessment->value)
            ->whereNull('validated_at')
            ->whereNull('rejected_at');
    }

    public function isSelfAssessment(): bool
    {
        return $this->method === AssessmentMethod::SelfAssessment;
    }

    public function isPending(): bool
    {
        return $this->isSelfAssessment() && $this->validated_at === null && $this->rejected_at === null;
    }

    public function isExpired(?Carbon $on = null): bool
    {
        return $this->valid_until !== null && $this->valid_until->lt(($on ?? today())->startOfDay());
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

    /** @return BelongsTo<Employee, $this> */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }

    protected static function newFactory(): AssessmentFactory
    {
        return AssessmentFactory::new();
    }
}
