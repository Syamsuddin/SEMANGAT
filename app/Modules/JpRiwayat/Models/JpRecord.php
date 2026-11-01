<?php

namespace App\Modules\JpRiwayat\Models;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Database\Factories\JpRecordFactory;
use App\Modules\JpRiwayat\Enums\JpMode;
use App\Modules\JpRiwayat\Enums\JpStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * CatatanJP (M12). Ditulis hanya lewat Actions modul JpRiwayat.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $year
 * @property string|null $sourceable_type
 * @property int|null $sourceable_id
 * @property string $title
 * @property ActivityPortion $portion
 * @property JpMode $mode
 * @property string $jp
 * @property Carbon $activity_start
 * @property Carbon $activity_end
 * @property JpStatus $status
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property string|null $reject_reason
 * @property-read Employee $employee
 * @property-read Certificate|null $certificate
 * @property-read User|null $verifier
 */
class JpRecord extends Model
{
    /** @use HasFactory<JpRecordFactory> */
    use HasFactory;

    protected $table = 'out_jp_records';

    protected $fillable = [
        'employee_id', 'year', 'sourceable_type', 'sourceable_id', 'title', 'portion', 'mode', 'jp',
        'activity_start', 'activity_end', 'status', 'verified_by', 'verified_at', 'reject_reason',
    ];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'portion' => ActivityPortion::class,
            'mode' => JpMode::class,
            'jp' => 'decimal:1',
            'activity_start' => 'date',
            'activity_end' => 'date',
            'status' => JpStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Cakupan baris (docs/05 M12: S/T/O/K/B; clo & dewan hanya agregat).
     *
     * @param  Builder<JpRecord>  $query
     * @return Builder<JpRecord>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('out_jp_records.employee_id', Employee::query()->visibleTo($user)->select('hr_employees.id'));
    }

    /**
     * @param  Builder<JpRecord>  $query
     * @return Builder<JpRecord>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('out_jp_records.status', JpStatus::Verified->value);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasOne<Certificate, $this> */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return MorphTo<Model, $this> */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): JpRecordFactory
    {
        return JpRecordFactory::new();
    }
}
