<?php

namespace App\Modules\JpRiwayat\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\JpRiwayat\Actions\VerifyCertificate;
use App\Modules\JpRiwayat\Models\JpRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * JPR-02: antrean verifikasi sertifikat luar bagi pengelola (seluruh) & koord. OPD (OPD-nya) — verifikasi
 * (JP boleh dikoreksi) atau tolak dengan alasan wajib.
 *
 * @property-read Collection<int, JpRecord> $pending
 */
#[Layout('layouts.app')]
class CertificateVerificationList extends Component
{
    public string $search = '';

    /** @var array<int|string, string> record_id => JP terkoreksi */
    public array $jp = [];

    /** @var array<int|string, string> record_id => alasan penolakan */
    public array $reasons = [];

    /** Dicek di setiap permintaan Livewire (bukan hanya muat awal), termasuk pencarian & aksi. */
    public function boot(): void
    {
        abort_unless($this->user()->can(Permission::JpVerify->value), 403);
    }

    /** @return Collection<int, JpRecord> */
    #[Computed]
    public function pending(): Collection
    {
        return JpRecord::query()->verifiableBy($this->user())
            ->when($this->search !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$this->search.'%')->orWhere('nip', 'like', $this->search.'%')))
            ->with(['employee:id,name,nip,opd_id', 'employee.opd:id,name', 'certificate:id,jp_record_id,issuer,number'])
            ->orderBy('created_at')
            ->limit(100)
            ->get();
    }

    public function verify(int $recordId, VerifyCertificate $verify): void
    {
        $this->resetErrorBag();
        $verify->verify($this->user(), $this->find($recordId), ($this->jp[$recordId] ?? '') === '' ? null : $this->jp[$recordId]);
        unset($this->pending, $this->jp[$recordId]);
        $this->dispatch('toast', type: 'success', message: __('jp.verify.verified'));
    }

    public function reject(int $recordId, VerifyCertificate $verify): void
    {
        $this->resetErrorBag();
        $verify->reject($this->user(), $this->find($recordId), $this->reasons[$recordId] ?? null);
        unset($this->pending, $this->reasons[$recordId]);
        $this->dispatch('toast', type: 'success', message: __('jp.verify.rejected'));
    }

    public function render(): View
    {
        return view('jp::livewire.certificate-verification-list')->title(__('jp.verify.title'));
    }

    private function find(int $recordId): JpRecord
    {
        return JpRecord::query()->verifiableBy($this->user())->with('employee')->findOrFail($recordId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
