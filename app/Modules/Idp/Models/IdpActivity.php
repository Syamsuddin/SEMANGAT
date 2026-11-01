<?php

namespace App\Modules\Idp\Models;

use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Idp\Enums\ActivityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aktivitas 10:20:70 per butir IDP. program_id menunjuk katalog M08 (F5).
 *
 * @property int $id
 * @property int $idp_item_id
 * @property ActivityPortion $portion
 * @property string $title
 * @property int|null $program_id
 * @property int $planned_jp
 * @property Carbon|null $planned_start
 * @property Carbon|null $planned_end
 * @property ActivityStatus $status
 * @property-read IdpItem $item
 */
class IdpActivity extends Model
{
    protected $table = 'pln_idp_activities';

    protected $fillable = ['idp_item_id', 'portion', 'title', 'program_id', 'planned_jp', 'planned_start', 'planned_end', 'status'];

    protected $attributes = ['status' => 'planned', 'planned_jp' => 0];

    protected function casts(): array
    {
        return [
            'portion' => ActivityPortion::class,
            'status' => ActivityStatus::class,
            'planned_jp' => 'integer',
            'planned_start' => 'date',
            'planned_end' => 'date',
        ];
    }

    /** @return BelongsTo<IdpItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(IdpItem::class, 'idp_item_id');
    }
}
