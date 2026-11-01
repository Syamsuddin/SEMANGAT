<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Idp\Models\IdpActivity;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Actions\RecordJp;
use App\Modules\JpRiwayat\Actions\UploadCertificate;
use App\Modules\JpRiwayat\Enums\JpMode;
use App\Modules\JpRiwayat\Enums\JpStatus;
use App\Modules\JpRiwayat\Livewire\JpOverview;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\JpBalance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(config('semangat.jp.certificates.disk'));
    $this->employee = Employee::factory()->create();
    $this->user = employeeUser($this->employee);
});

it('JPR-03 pencatatan otomatis dari sumber (M08/M09/M10) → verified, langsung dihitung, idempoten per sumber', function () {
    // Sumber nyata (kelas M09, sesi M10, pembelajaran tempat kerja M08) hadir di F5/F8; aktivitas IDP berperan sebagai model sumber.
    $source = IdpActivity::query()->create(['idp_item_id' => idpWithItems($this->employee)->items[0]->id, 'portion' => 70, 'title' => 'Proyek perubahan']);
    $record = fn () => app(RecordJp::class)->automatic($this->employee->id, $source, 'Proyek perubahan', ActivityPortion::Workplace, JpMode::NonClassical, 6, Carbon::parse(now()->year.'-02-01'), Carbon::parse(now()->year.'-02-20'));

    $first = $record();
    $second = $record();

    expect($first->is($second))->toBeTrue()
        ->and(JpRecord::query()->count())->toBe(1)
        ->and($first->status)->toBe(JpStatus::Verified)
        ->and($first->sourceable_id)->toBe($source->id)
        ->and(app(JpBalance::class)->forEmployee($this->employee->id, (int) now()->year)['verified'])->toBe(6.0);

    Livewire::actingAs($this->user)->test(JpOverview::class)->assertSee('Proyek perubahan')->assertSee('Tercatat otomatis');
})->group('jp');

it('JPR-04 riwayat bangkom lengkap lintas tahun; saldo mengikuti tahun terpilih', function () {
    $year = (int) now()->year;
    JpRecord::factory()->verified()->for($this->employee, 'employee')->create(['title' => 'Diklat tahun lalu', 'jp' => 24, 'year' => $year - 1, 'activity_start' => ($year - 1).'-05-01', 'activity_end' => ($year - 1).'-05-03']);
    JpRecord::factory()->for($this->employee, 'employee')->create(['title' => 'Seminar tahun ini', 'jp' => 4]);

    Livewire::actingAs($this->user)->test(JpOverview::class)
        ->assertSee('Diklat tahun lalu')
        ->assertSee('Seminar tahun ini')
        ->assertSee('0 dari 20 JP')
        ->set('year', $year - 1)
        ->assertSee('24 dari 20 JP')
        ->assertSee('Terpenuhi');
})->group('jp');

it('JPR-04 atasan melihat riwayat bawahan langsung tanpa formulir unggah; berkas sertifikat terunduh sebagai lampiran', function () {
    $atasan = userWithRole(Role::Atasan);
    $sub = Employee::query()->where('supervisor_id', $atasan->employee_id)->firstOrFail();
    $record = app(UploadCertificate::class)->handle(employeeUser($sub), jpInput(), certificatePdf());

    $this->actingAs($atasan)->get(route('jp.records.employee', $sub))
        ->assertOk()->assertSee('Pelatihan Analisis Kebijakan')->assertDontSee('Unggah sertifikat bangkom luar');

    $this->actingAs($atasan)->get(route('jp.certificates.download', $record))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload('sertifikat-'.$record->id.'.pdf');
})->group('jp');
