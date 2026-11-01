<?php

namespace App\Modules\Idp\Models;

use App\Modules\Idp\Database\Factories\IdpItemFactory;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\SkpWorkResult;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\Gap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Butir kompetensi prioritas IDP (IDP-02). skp_work_result_id NULL = belum tertaut SKP (IDP-10).
 *
 * @property int $id
 * @property int $idp_id
 * @property int $competency_id
 * @property int|null $gap_id
 * @property int $start_level
 * @property int $target_level
 * @property int|null $skp_work_result_id
 * @property string|null $success_indicator
 * @property int|null $mentor_employee_id
 * @property int $position
 * @property-read Idp $idp
 * @property-read Competency $competency
 * @property-read Gap|null $gap
 * @property-read SkpWorkResult|null $workResult
 * @property-read Employee|null $mentor
 * @property-read Collection<int, IdpActivity> $activities
 */
class IdpItem extends Model
{
    /** @use HasFactory<IdpItemFactory> */
    use HasFactory;

    protected $table = 'pln_idp_items';

    protected $fillable = [
        'idp_id', 'competency_id', 'gap_id', 'start_level', 'target_level', 'skp_work_result_id', 'success_indicator', 'mentor_employee_id', 'position',
    ];

    protected function casts(): array
    {
        return ['start_level' => 'integer', 'target_level' => 'integer', 'position' => 'integer'];
    }

    public function isLinked(): bool
    {
        return $this->skp_work_result_id !== null;
    }

    /** @return BelongsTo<Idp, $this> */
    public function idp(): BelongsTo
    {
        return $this->belongsTo(Idp::class);
    }

    /** @return BelongsTo<Competency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /** @return BelongsTo<Gap, $this> */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /** @return BelongsTo<SkpWorkResult, $this> */
    public function workResult(): BelongsTo
    {
        return $this->belongsTo(SkpWorkResult::class, 'skp_work_result_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mentor_employee_id');
    }

    /** @return HasMany<IdpActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(IdpActivity::class)->orderBy('portion')->orderBy('id');
    }

    protected static function newFactory(): IdpItemFactory
    {
        return IdpItemFactory::new();
    }
}
