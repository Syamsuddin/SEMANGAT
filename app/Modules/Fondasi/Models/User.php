<?php

namespace App\Modules\Fondasi\Models;

use App\Modules\Fondasi\Database\Factories\UserFactory;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Akun login. Peran spatie adalah hasil materialisasi SyncUserRoles — jangan assignRole() di tempat lain.
 *
 * @property int $id
 * @property int|null $employee_id
 * @property string $username
 * @property bool $is_active
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $last_login_at
 * @property array<string, bool>|null $notification_prefs
 * @property-read Employee|null $employee
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['employee_id', 'username', 'password', 'is_active', 'notification_prefs'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'notification_prefs' => 'array',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<RoleAssignment, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function hasRoleEnum(Role $role): bool
    {
        return $this->hasRole($role->value);
    }

    public function requiresTwoFactor(): bool
    {
        foreach (Role::cases() as $role) {
            if ($role->requiresTwoFactor() && $this->hasRoleEnum($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function displayName(): string
    {
        return $this->employee !== null ? $this->employee->name : $this->username;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
