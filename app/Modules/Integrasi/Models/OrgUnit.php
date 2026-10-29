<?php

namespace App\Modules\Integrasi\Models;

use App\Modules\Integrasi\Database\Factories\OrgUnitFactory;
use App\Modules\Integrasi\Enums\OrgUnitLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unit organisasi ⇅ — read-only kecuali lewat adaptor M02.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property OrgUnitLevel $level
 * @property int|null $parent_id
 * @property int|null $opd_id
 */
class OrgUnit extends Model
{
    /** @use HasFactory<OrgUnitFactory> */
    use HasFactory;

    protected $table = 'hr_org_units';

    protected $fillable = ['parent_id', 'code', 'name', 'level', 'opd_id', 'source', 'external_id', 'synced_at'];

    protected function casts(): array
    {
        return ['level' => OrgUnitLevel::class, 'synced_at' => 'datetime'];
    }

    /** @return BelongsTo<OrgUnit, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'parent_id');
    }

    /** @return BelongsTo<OrgUnit, $this> */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'opd_id');
    }

    protected static function newFactory(): OrgUnitFactory
    {
        return OrgUnitFactory::new();
    }
}
