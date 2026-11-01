<?php

namespace App\Modules\Idp\Models;

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Database\Factories\IdpFactory;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Enums\IdpTemplate;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * IDP pegawai per periode SKP (M06). Ditulis hanya lewat Actions modul Idp.
 *
 * @property int $id
 * @property int $employee_id
 * @property int|null $supervisor_id
 * @property int $period_year
 * @property IdpStatus $status
 * @property IdpTemplate $template
 * @property string|null $career_aspiration
 * @property int $target_jp
 * @property string|null $supervisor_note
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $closed_at
 * @property string|null $closing_reflection
 * @property string|null $closing_assessment
 * @property int $unlinked_items_count
 * @property-read Employee $employee
 * @property-read Employee|null $supervisor
 * @property-read Collection<int, IdpItem> $items
 * @property-read Collection<int, IdpCheckin> $checkins
 * @property-read IssuedDocument|null $learningPlan
 * @property-read IssuedDocument|null $learningReport
 */
class Idp extends Model
{
    /** @use HasFactory<IdpFactory> */
    use HasFactory;

    public const MAX_ITEMS = 3;

    public const MIN_ITEMS = 2;

    protected $table = 'pln_idps';

    protected $fillable = [
        'employee_id', 'supervisor_id', 'period_year', 'status', 'template', 'career_aspiration', 'target_jp', 'supervisor_note',
        'submitted_at', 'approved_at', 'closed_at', 'closing_reflection', 'closing_assessment', 'unlinked_items_count',
    ];

    protected $attributes = ['status' => 'draft', 'template' => 'standard', 'target_jp' => 20, 'unlinked_items_count' => 0];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'status' => IdpStatus::class,
            'template' => IdpTemplate::class,
            'target_jp' => 'integer',
            'unlinked_items_count' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Cakupan baris (docs/05 M06: S/T/O/B; clo hanya agregat).
     *
     * @param  Builder<Idp>  $query
     * @return Builder<Idp>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('pln_idps.employee_id', Employee::query()->visibleTo($user)->select('hr_employees.id'));
    }

    /**
     * IDP-10: punya butir tanpa tautan RHK, sudah diajukan.
     *
     * @param  Builder<Idp>  $query
     * @return Builder<Idp>
     */
    public function scopeUnlinked(Builder $query): Builder
    {
        return $query->where('unlinked_items_count', '>', 0)
            ->whereNotIn('status', [IdpStatus::Draft->value, IdpStatus::Cancelled->value]);
    }

    public function isEditable(): bool
    {
        return $this->status === IdpStatus::Draft;
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /** @return HasMany<IdpItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(IdpItem::class)->orderBy('position');
    }

    /** @return HasMany<IdpCheckin, $this> */
    public function checkins(): HasMany
    {
        return $this->hasMany(IdpCheckin::class)->orderBy('quarter');
    }

    /** @return MorphOne<IssuedDocument, $this> */
    public function learningPlan(): MorphOne
    {
        return $this->issuedDocument(DocumentType::LearningPlan);
    }

    /**
     * JPR-05: Laporan Hasil Pembelajaran terbitan (M12) atas IDP `closed`.
     *
     * @return MorphOne<IssuedDocument, $this>
     */
    public function learningReport(): MorphOne
    {
        return $this->issuedDocument(DocumentType::LearningReport);
    }

    /** @return MorphOne<IssuedDocument, $this> */
    private function issuedDocument(DocumentType $type): MorphOne
    {
        // Filter di closure ofMany agar ikut masuk subkueri MAX — filter di luar tidak mempersempit agregatnya.
        return $this->morphOne(IssuedDocument::class, 'documentable')
            ->ofMany(['issued_at' => 'max', 'id' => 'max'], fn ($q) => $q->where('type', $type->value)->whereNull('revoked_at'));
    }

    protected static function newFactory(): IdpFactory
    {
        return IdpFactory::new();
    }
}
