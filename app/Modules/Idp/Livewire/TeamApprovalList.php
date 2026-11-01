<?php

namespace App\Modules\Idp\Livewire;

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Actions\ApproveIdpBatch;
use App\Modules\Idp\Actions\CloseIdp;
use App\Modules\Idp\Actions\RecordCheckin;
use App\Modules\Idp\Actions\ReturnIdp;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpCheckin;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * IDP-04: persetujuan IDP tim (tunggal/massal dengan catatan); IDP-06/07: catatan check-in & penutupan oleh atasan aktif.
 *
 * @property-read Collection<int, Idp> $pending
 */
#[Layout('layouts.app')]
class TeamApprovalList extends Component
{
    /** @var list<int|string> */
    public array $selected = [];

    public ?string $note = null;

    /** @var array<int|string, string> idp_id => catatan pengembalian */
    public array $returnNotes = [];

    /** @var array<int|string, string> checkin_id => catatan atasan */
    public array $checkinNotes = [];

    /** @var array<int|string, string> idp_id => penilaian penerapan */
    public array $assessments = [];

    public function mount(): void
    {
        abort_unless($this->user()->hasRole(Role::Atasan->value), 403);
    }

    /** @return Collection<int, Idp> */
    #[Computed]
    public function pending(): Collection
    {
        return Idp::query()->visibleTo($this->user())
            ->where('status', IdpStatus::Submitted->value)
            ->where('supervisor_id', $this->user()->employee_id)
            ->with(['employee:id,name,nip,position_id', 'employee.position:id,name', 'items.competency:id,name', 'items.workResult:id,code', 'items.activities'])
            ->orderBy('submitted_at')->get();
    }

    /** @return Collection<int, Idp> */
    #[Computed]
    public function active(): Collection
    {
        return Idp::query()->visibleTo($this->user())
            ->where('status', IdpStatus::Active->value)
            ->whereHas('employee', fn ($q) => $q->where('supervisor_id', $this->user()->employee_id))
            ->with(['employee:id,name,nip', 'items.competency:id,name', 'checkins', 'learningPlan'])
            ->orderBy('employee_id')->get();
    }

    public function toggleAll(): void
    {
        $ids = $this->pending->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selected = count($this->selected) === count($ids) ? [] : $ids;
    }

    public function approveSelected(ApproveIdpBatch $approve): void
    {
        $this->resetErrorBag();
        $done = $approve->handle($this->user(), array_map('intval', $this->selected), $this->note);
        $this->reset('selected', 'note');
        unset($this->pending, $this->active);
        $this->dispatch('toast', type: 'success', message: __('idp.team.approved', ['count' => $done->count()]));
    }

    public function returnIdp(int $idpId, ReturnIdp $return): void
    {
        $this->resetErrorBag();
        $return->handle($this->user(), $this->find($idpId), $this->returnNotes[$idpId] ?? null);
        unset($this->pending, $this->returnNotes[$idpId]);
        $this->selected = array_values(array_diff($this->selected, [(string) $idpId, $idpId]));
        $this->dispatch('toast', type: 'success', message: __('idp.team.returned'));
    }

    public function reviewCheckin(int $checkinId, RecordCheckin $record): void
    {
        $checkin = IdpCheckin::query()->whereIn('idp_id', Idp::query()->visibleTo($this->user())->select('id'))->findOrFail($checkinId);
        $record->review($this->user(), $checkin, $this->checkinNotes[$checkinId] ?? null);
        unset($this->active, $this->checkinNotes[$checkinId]);
        $this->dispatch('toast', type: 'success', message: __('idp.team.checkin_reviewed'));
    }

    public function close(int $idpId, CloseIdp $close): void
    {
        $close->close($this->user(), $this->find($idpId), $this->assessments[$idpId] ?? null);
        unset($this->active, $this->assessments[$idpId]);
        $this->dispatch('toast', type: 'success', message: __('idp.team.closed'));
    }

    public function render(): View
    {
        return view('idp::livewire.team-approval-list')->title(__('idp.team.title'));
    }

    private function find(int $idpId): Idp
    {
        return Idp::query()->visibleTo($this->user())->with('employee')->findOrFail($idpId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
