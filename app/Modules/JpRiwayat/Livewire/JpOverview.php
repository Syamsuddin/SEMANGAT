<?php

namespace App\Modules\JpRiwayat\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Actions\StartIdp;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Actions\IssueLearningReport;
use App\Modules\JpRiwayat\Actions\UploadCertificate;
use App\Modules\JpRiwayat\Enums\JpMode;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\JpBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * JP saya / JP pegawai dalam cakupan: saldo 20 JP tahun berjalan (JPR-01), unggah sertifikat luar (JPR-02, pemilik saja),
 * riwayat bangkom lengkap lintas tahun (JPR-04), Laporan Hasil Pembelajaran IDP `closed` tahun terpilih (JPR-05) —
 * juga untuk IDP tahun lalu. `?tahun=` memilih tahun awal (tautan notifikasi Laporan).
 *
 * @property-read Collection<int, JpRecord> $records
 * @property-read Idp|null $closedIdp
 * @property-read Employee $employee
 * @property-read bool $isOwner
 */
#[Layout('layouts.app')]
class JpOverview extends Component
{
    use WithFileUploads;

    public ?int $employeeId = null;

    public int $year;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(?Employee $employee = null): void
    {
        $user = $this->user();

        if ($employee?->exists) {
            abort_unless(Employee::query()->visibleTo($user)->whereKey($employee->id)->exists(), 403);
            $this->employeeId = $employee->id;
        } else {
            abort_if($user->employee_id === null, 403);
            $this->employeeId = $user->employee_id;
        }

        $year = (int) request()->query('tahun', (string) now()->year);
        $this->year = $year >= 2000 && $year <= (int) now()->year ? $year : (int) now()->year;
        $this->resetForm();
    }

    #[Computed]
    public function employee(): Employee
    {
        return Employee::query()->visibleTo($this->user())->findOrFail($this->employeeId);
    }

    #[Computed]
    public function isOwner(): bool
    {
        return $this->user()->employee_id === $this->employeeId;
    }

    /** @return array{year: int, verified: float, pending: float, target: int, fulfilled: bool, percent: int} */
    #[Computed]
    public function balance(): array
    {
        return app(JpBalance::class)->forEmployee((int) $this->employeeId, $this->year);
    }

    /** @return Collection<int, JpRecord> */
    #[Computed]
    public function records(): Collection
    {
        return JpRecord::query()->visibleTo($this->user())
            ->where('employee_id', $this->employeeId)
            ->with('certificate:id,jp_record_id,issuer,number,is_internal')
            ->orderByDesc('activity_end')->orderByDesc('id')
            ->get();
    }

    /** @return list<int> */
    #[Computed]
    public function years(): array
    {
        $idpYears = Idp::query()->visibleTo($this->user())->where('employee_id', $this->employeeId)->pluck('period_year');

        return collect([(int) now()->year, $this->year])->merge($this->records->pluck('year'))->merge($idpYears)
            ->map(fn ($y) => (int) $y)->unique()->sortDesc()->values()->all();
    }

    /** IDP tahun terpilih yang sudah ditutup — sumber Laporan Hasil Pembelajaran (JPR-05). */
    #[Computed]
    public function closedIdp(): ?Idp
    {
        $idp = StartIdp::current((int) $this->employeeId, $this->year);

        return $idp !== null && $idp->status === IdpStatus::Closed && $this->user()->can('view', $idp) ? $idp->load('learningReport') : null;
    }

    /** @return array<int, string> */
    public function portionOptions(): array
    {
        return collect(ActivityPortion::cases())->mapWithKeys(fn (ActivityPortion $p) => [$p->value => $p->label()])->all();
    }

    /** @return array<string, string> */
    public function modeOptions(): array
    {
        return collect(JpMode::cases())->mapWithKeys(fn (JpMode $m) => [$m->value => $m->label()])->all();
    }

    public function updatedYear(): void
    {
        unset($this->balance, $this->closedIdp);
    }

    public function issueReport(IssueLearningReport $issue): void
    {
        $idp = $this->closedIdp;
        abort_unless($this->isOwner && $idp !== null, 403);
        $this->resetErrorBag();
        $issue->handle($this->user(), $idp);
        unset($this->closedIdp);
        $this->dispatch('toast', type: 'success', message: __('jp.report.queued'));
    }

    public function upload(UploadCertificate $upload): void
    {
        abort_unless($this->isOwner, 403);
        $this->resetErrorBag();
        try {
            $upload->handle($this->user(), $this->form, $this->file instanceof UploadedFile ? $this->file : null);
        } catch (ValidationException $e) {
            // Kunci Action (`title`) → kunci isian formulir ini (`form.title`); berkas tetap `file`.
            throw ValidationException::withMessages(collect($e->errors())
                ->mapWithKeys(fn (array $messages, string $key) => [$key === 'file' ? 'file' : 'form.'.$key => $messages])
                ->all());
        }
        $this->resetForm();
        unset($this->records, $this->balance, $this->years);
        $this->dispatch('toast', type: 'success', message: __('jp.upload.done'));
    }

    public function render(): View
    {
        return view('jp::livewire.jp-overview')->title($this->isOwner ? __('jp.overview.title') : __('jp.overview.title_employee', ['name' => $this->employee->name]));
    }

    private function resetForm(): void
    {
        $this->form = ['title' => '', 'issuer' => '', 'number' => '', 'portion' => ActivityPortion::Formal->value, 'mode' => JpMode::Classical->value, 'jp' => '', 'activity_start' => '', 'activity_end' => ''];
        $this->file = null;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
