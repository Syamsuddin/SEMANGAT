<?php

namespace App\Modules\Fondasi\Models;

use App\Modules\Fondasi\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Penugasan peran manual tercatat (sys_role_assignments). Bukan untuk peran turunan.
 *
 * @property int $id
 * @property int $user_id
 * @property Role $role
 * @property string|null $scope_type
 * @property int|null $scope_id
 * @property string $reason
 * @property int|null $assigned_by
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $revoked_at
 */
class RoleAssignment extends Model
{
    public const SCOPE_OPD = 'opd';

    protected $table = 'sys_role_assignments';

    protected $fillable = ['user_id', 'role', 'scope_type', 'scope_id', 'reason', 'assigned_by', 'starts_at', 'ends_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<RoleAssignment>  $query
     * @return Builder<RoleAssignment>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();

        return $query->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
