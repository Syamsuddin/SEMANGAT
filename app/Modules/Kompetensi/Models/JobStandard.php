<?php

namespace App\Modules\Kompetensi\Models;

use App\Modules\Integrasi\Models\Position;
use App\Modules\Kompetensi\Database\Factories\JobStandardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Standar Kompetensi Jabatan (KOM-01).
 *
 * @property int $id
 * @property string $position_code
 * @property string $name
 * @property Carbon $effective_on
 * @property-read Collection<int, JobStandardItem> $items
 */
class JobStandard extends Model
{
    /** @use HasFactory<JobStandardFactory> */
    use HasFactory;

    protected $table = 'cmp_job_standards';

    protected $fillable = ['position_code', 'name', 'effective_on', 'source', 'external_id', 'synced_at'];

    protected function casts(): array
    {
        return ['effective_on' => 'date', 'synced_at' => 'datetime'];
    }

    /** @return HasMany<JobStandardItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(JobStandardItem::class);
    }

    /**
     * SKJ acuan jabatan: hr_positions.job_standard_id bila diisi sumber; selain itu SKJ terbaru
     * yang sudah berlaku dengan position_code = kode jabatan. M03 tidak menulis tabel ⇅ M02.
     */
    public static function forPosition(?Position $position): ?self
    {
        if ($position === null) {
            return null;
        }

        $linkedId = $position->getAttribute('job_standard_id');
        if ($linkedId !== null) {
            return self::query()->with('items.competency')->find($linkedId);
        }

        return self::query()
            ->with('items.competency')
            ->where('position_code', $position->code)
            ->where('effective_on', '<=', today())
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  Builder<JobStandard>  $query
     * @return Builder<JobStandard>
     */
    public function scopeEffective(Builder $query): Builder
    {
        return $query->where('effective_on', '<=', today());
    }

    protected static function newFactory(): JobStandardFactory
    {
        return JobStandardFactory::new();
    }
}
