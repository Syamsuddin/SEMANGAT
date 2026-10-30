<?php

namespace App\Modules\Kompetensi\Models;

use App\Modules\Kompetensi\Database\Factories\JobStandardItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Butir SKJ: kompetensi + level dipersyaratkan.
 *
 * @property int $id
 * @property int $job_standard_id
 * @property int $competency_id
 * @property int $required_level
 * @property-read Competency $competency
 */
class JobStandardItem extends Model
{
    /** @use HasFactory<JobStandardItemFactory> */
    use HasFactory;

    protected $table = 'cmp_job_standard_items';

    protected $fillable = ['job_standard_id', 'competency_id', 'required_level'];

    protected function casts(): array
    {
        return ['required_level' => 'integer'];
    }

    /** @return BelongsTo<JobStandard, $this> */
    public function jobStandard(): BelongsTo
    {
        return $this->belongsTo(JobStandard::class);
    }

    /** @return BelongsTo<Competency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    protected static function newFactory(): JobStandardItemFactory
    {
        return JobStandardItemFactory::new();
    }
}
