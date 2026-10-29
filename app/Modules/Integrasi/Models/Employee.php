<?php

namespace App\Modules\Integrasi\Models;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Database\Factories\EmployeeFactory;
use App\Modules\Integrasi\Enums\EmployeeStatus;
use App\Modules\Integrasi\Enums\EmploymentType;
use App\Support\Scope\DataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Pegawai ⇅ — read-only kecuali lewat adaptor M02; tidak pernah dihapus.
 *
 * @property int $id
 * @property string $nip
 * @property string $name
 * @property EmploymentType $employment_type
 * @property EmployeeStatus $status
 * @property int $org_unit_id
 * @property int $opd_id
 * @property int|null $position_id
 * @property int|null $supervisor_id
 * @property string|null $email
 * @property string|null $phone
 * @property string $source
 * @property Carbon $synced_at
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $table = 'hr_employees';

    protected $fillable = [
        'nip', 'name', 'employment_type', 'status', 'org_unit_id', 'opd_id', 'position_id',
        'supervisor_id', 'email', 'phone', 'source', 'external_id', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Satu-satunya pintu query data pegawai (docs/21). Aturan cakupan di DataScope.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return DataScope::for($user)->applyToEmployees($query);
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EmployeeStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }

    /** @return BelongsTo<OrgUnit, $this> */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    /** @return BelongsTo<OrgUnit, $this> */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'opd_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /** @return HasMany<Employee, $this> */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
