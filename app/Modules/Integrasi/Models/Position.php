<?php

namespace App\Modules\Integrasi\Models;

use App\Modules\Integrasi\Database\Factories\PositionFactory;
use App\Modules\Integrasi\Enums\PositionTier;
use App\Modules\Integrasi\Enums\PositionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jabatan ⇅.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property PositionType $type
 * @property PositionTier $tier
 */
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $table = 'hr_positions';

    protected $fillable = ['code', 'name', 'type', 'grade_class', 'family', 'tier', 'source', 'external_id', 'synced_at'];

    protected function casts(): array
    {
        return ['type' => PositionType::class, 'tier' => PositionTier::class, 'synced_at' => 'datetime'];
    }

    protected static function newFactory(): PositionFactory
    {
        return PositionFactory::new();
    }
}
