<?php

namespace App\Modules\Kompetensi\Models;

use App\Modules\Kompetensi\Database\Factories\CompetencyFactory;
use App\Modules\Kompetensi\Enums\CompetencyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kamus kompetensi (KOM-02).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property CompetencyType $type
 * @property string|null $description
 * @property array<string, string>|null $level_descriptors
 * @property string|null $steward
 * @property bool $is_active
 */
class Competency extends Model
{
    /** @use HasFactory<CompetencyFactory> */
    use HasFactory;

    protected $table = 'cmp_competencies';

    protected $fillable = ['code', 'name', 'type', 'description', 'level_descriptors', 'steward', 'is_active'];

    protected function casts(): array
    {
        return ['type' => CompetencyType::class, 'level_descriptors' => 'array', 'is_active' => 'boolean'];
    }

    protected static function newFactory(): CompetencyFactory
    {
        return CompetencyFactory::new();
    }
}
