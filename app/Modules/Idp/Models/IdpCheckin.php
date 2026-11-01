<?php

namespace App\Modules\Idp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Check-in triwulanan IDP (IDP-06), menumpang dialog kinerja SKP.
 *
 * @property int $id
 * @property int $idp_id
 * @property Carbon $checkin_on
 * @property int $quarter
 * @property string $employee_note
 * @property string|null $supervisor_note
 * @property array<int|string, int> $progress item_id => persen
 * @property-read Idp $idp
 */
class IdpCheckin extends Model
{
    protected $table = 'pln_idp_checkins';

    protected $fillable = ['idp_id', 'checkin_on', 'quarter', 'employee_note', 'supervisor_note', 'progress'];

    protected function casts(): array
    {
        return ['checkin_on' => 'date', 'quarter' => 'integer', 'progress' => 'array'];
    }

    /** @return BelongsTo<Idp, $this> */
    public function idp(): BelongsTo
    {
        return $this->belongsTo(Idp::class);
    }
}
