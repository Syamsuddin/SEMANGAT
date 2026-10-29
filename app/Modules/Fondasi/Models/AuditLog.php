<?php

namespace App\Modules\Fondasi\Models;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Exceptions\ImmutableAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Jejak audit append-only (FON-05). Tulis hanya lewat App\Support\AuditLogger.
 * Penjaga lapis aplikasi; lapis DB: hak INSERT/SELECT saja di produksi (docs/21).
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string|null $reason
 * @property array<string, mixed>|null $changes
 * @property string $ip_address
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sys_audit_logs';

    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'reason', 'changes', 'ip_address'];

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected function casts(): array
    {
        return ['action' => AuditAction::class, 'changes' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new ImmutableAuditLog);
        static::deleting(fn () => throw new ImmutableAuditLog);
    }

    /**
     * Penghapusan/ubah massal lewat query builder juga ditolak.
     *
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): ImmutableAuditLogBuilder
    {
        return new ImmutableAuditLogBuilder($query);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
