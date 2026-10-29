<?php

namespace Database\Seeders;

use App\Modules\Fondasi\Actions\AssignRole;
use App\Modules\Fondasi\Actions\SyncEmployeeAccounts;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Integrasi\Models\OrgUnit;
use App\Modules\Integrasi\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dev saja (docs/10): satu akun per peran, NIP fiktif berawalan 1999, kata sandi `semangat-dev-2026`.
 * Tidak pernah berisi data produksi.
 */
class DevUserSeeder extends Seeder
{
    public const PASSWORD = 'semangat-dev-2026';

    /** @var array<string, string> peran → NIP */
    public const ACCOUNTS = [
        'asn' => '199901012020011001',
        'atasan' => '199901012010011002',
        'koordinator_opd' => '199901012012011003',
        'fasilitator' => '199901012015011004',
        'pengelola' => '199901012011011005',
        'clo' => '199901012005011006',
        'dewan_pengarah' => '199901012000011007',
        'admin_teknis' => '199901012018011008',
        'auditor' => '199901012016011009',
    ];

    public function run(AssignRole $assign, SyncEmployeeAccounts $accounts): void
    {
        $opd = OrgUnit::factory()->create(['code' => 'OPD-BKPSDM', 'external_id' => 'OPD-BKPSDM', 'name' => 'Badan Kepegawaian dan Pengembangan SDM']);
        $other = OrgUnit::factory()->create(['code' => 'OPD-DINKES', 'external_id' => 'OPD-DINKES', 'name' => 'Dinas Kesehatan']);
        $position = Position::factory()->create(['name' => 'Analis SDM Aparatur']);

        $people = [];
        foreach (self::ACCOUNTS as $role => $nip) {
            $people[$role] = Employee::factory()->inOpd($role === 'dewan_pengarah' ? $other : $opd)->create([
                'nip' => $nip,
                'external_id' => $nip,
                'name' => 'Pegawai Contoh '.Role::from($role)->label(),
                'position_id' => $position->id,
            ]);
        }
        $people['asn']->forceFill(['supervisor_id' => $people['atasan']->id])->save();
        Employee::factory()->count(3)->supervisedBy($people['atasan'])->create(['position_id' => $position->id]);
        Employee::factory()->count(5)->inOpd($other)->create();

        $accounts();
        User::query()->update(['password' => Hash::make(self::PASSWORD)]);

        foreach (Role::assignable() as $role) {
            $user = User::query()->where('username', self::ACCOUNTS[$role->value])->firstOrFail();
            $assign($user, $role, 'Akun contoh pengembangan', opdId: $role->requiresOpdScope() ? $opd->id : null);
        }
    }
}
