<?php

namespace App\Modules\Integrasi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris gagal dalam satu run (laporan per baris + antrean ulang).
 *
 * @property int $id
 * @property int $sync_run_id
 * @property int|null $row_number
 * @property string|null $external_id
 * @property array<string, mixed> $payload
 * @property string $error
 */
class SyncFailure extends Model
{
    protected $table = 'int_sync_failures';

    protected $fillable = ['sync_run_id', 'row_number', 'external_id', 'payload', 'error', 'retried_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'retried_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<SyncRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'sync_run_id');
    }
}
