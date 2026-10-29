<?php

namespace App\Modules\Integrasi\Models;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Database\Factories\SyncRunFactory;
use App\Modules\Integrasi\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Catatan satu run sinkron/impor (INT-08).
 *
 * @property int $id
 * @property string $adapter
 * @property string|null $source_ref
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property SyncStatus $status
 * @property int $rows_ok
 * @property int $rows_failed
 * @property string|null $error
 * @property int|null $triggered_by
 */
class SyncRun extends Model
{
    /** @use HasFactory<SyncRunFactory> */
    use HasFactory;

    protected $table = 'int_sync_runs';

    protected $fillable = ['adapter', 'source_ref', 'started_at', 'finished_at', 'status', 'rows_ok', 'rows_failed', 'error', 'triggered_by'];

    protected function casts(): array
    {
        return [
            'status' => SyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'rows_ok' => 'integer',
            'rows_failed' => 'integer',
        ];
    }

    /** @return HasMany<SyncFailure, $this> */
    public function failures(): HasMany
    {
        return $this->hasMany(SyncFailure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    protected static function newFactory(): SyncRunFactory
    {
        return SyncRunFactory::new();
    }
}
