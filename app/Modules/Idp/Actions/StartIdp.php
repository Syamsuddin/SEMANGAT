<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Services\DraftBuilder;
use App\Modules\Integrasi\Models\Employee;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/** IDP-01 "Susun IDP": membuat draf otomatis untuk periode berjalan; IDP terbuka yang sudah ada dikembalikan apa adanya. */
class StartIdp
{
    public function __construct(private readonly DraftBuilder $drafts, private readonly AuditLogger $audit) {}

    public function handle(User $user, ?int $periodYear = null): Idp
    {
        abort_if($user->employee_id === null || ! $user->is_active, 403);

        $periodYear ??= (int) now()->year;
        $existing = self::current($user->employee_id, $periodYear);
        if ($existing !== null) {
            return $existing;
        }

        $employee = Employee::query()->findOrFail($user->employee_id);

        return DB::transaction(function () use ($user, $employee, $periodYear) {
            $idp = Idp::query()->create(['employee_id' => $employee->id, 'period_year' => $periodYear, 'status' => IdpStatus::Draft]);

            foreach ($this->drafts->for($employee, $periodYear) as $i => $line) {
                $idp->items()->create([
                    'competency_id' => $line->competencyId,
                    'gap_id' => $line->gapId,
                    'start_level' => $line->startLevel,
                    'target_level' => $line->targetLevel,
                    'skp_work_result_id' => $line->workResultId,
                    'position' => $i + 1,
                ]);
            }

            $this->audit->log(AuditAction::Create, $idp, changes: ['status' => IdpStatus::Draft->value, 'items' => $idp->items()->count()], userId: $user->id);

            return $idp;
        });
    }

    /** IDP terbuka (bukan cancelled) pegawai pada periode. */
    public static function current(int $employeeId, int $periodYear): ?Idp
    {
        return Idp::query()->where('employee_id', $employeeId)->where('period_year', $periodYear)
            ->where('status', '!=', IdpStatus::Cancelled->value)->first();
    }
}
