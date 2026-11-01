<?php

namespace App\Modules\Idp\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Actions\CloseIdp;
use App\Modules\Idp\Actions\PublishLearningPlan;
use App\Modules\Idp\Actions\RecordCheckin;
use App\Modules\Idp\Actions\RemoveIdpItem;
use App\Modules\Idp\Actions\SaveIdpActivity;
use App\Modules\Idp\Actions\SaveIdpItem;
use App\Modules\Idp\Actions\StartIdp;
use App\Modules\Idp\Actions\SubmitIdp;
use App\Modules\Idp\Actions\UpdateIdp;
use App\Modules\Idp\Enums\ActivityPortion;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpActivity;
use App\Modules\Idp\Models\IdpItem;
use App\Modules\Idp\Services\CompetentExpectation;
use App\Modules\Idp\Services\DraftBuilder;
use App\Modules\JpRiwayat\Actions\IssueLearningReport;
use App\Modules\Kompetensi\Models\Competency;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * IDP saya: susun (IDP-01/02/03) → ajukan → terbitkan Rencana Pembelajaran (IDP-05) → check-in (IDP-06) → refleksi (IDP-07)
 * → Laporan Hasil Pembelajaran (JPR-05, M12).
 *
 * @property-read Idp|null $idp
 * @property-read array<int, string> $workResultOptions
 */
#[Layout('layouts.app')]
class IdpEditor extends Component
{
    /** @var array{career_aspiration: string|null, target_jp: int|string} */
    public array $form = ['career_aspiration' => null, 'target_jp' => 20];

    /** @var array<int|string, array<string, mixed>> item_id => isian butir */
    public array $items = [];

    /** @var array<string, mixed> */
    public array $newItem = [];

    /** @var array<int|string, array<string, mixed>> item_id => isian aktivitas baru */
    public array $activity = [];

    public ?string $checkinNote = null;

    /** @var array<int|string, int|string> item_id => persen */
    public array $progress = [];

    public ?string $reflection = null;

    public function mount(): void
    {
        abort_if($this->user()->employee_id === null, 403);
        $this->fillForms();
    }

    #[Computed]
    public function idp(): ?Idp
    {
        return StartIdp::current((int) $this->user()->employee_id, (int) now()->year)
            ?->load(['supervisor:id,name', 'items.competency:id,name', 'items.gap', 'items.workResult', 'items.mentor:id,name,nip', 'items.activities', 'checkins', 'learningPlan', 'learningReport', 'employee.position']);
    }

    /** @return array<int, string> */
    #[Computed]
    public function competencyOptions(): array
    {
        return Competency::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function workResultOptions(): array
    {
        $idp = $this->idp;

        return $idp === null ? [] : app(DraftBuilder::class)->workResultOptions($idp->employee, $idp->period_year);
    }

    #[Computed]
    public function expectation(): string
    {
        return app(CompetentExpectation::class)->for($this->idp?->employee->position?->tier);
    }

    /** @return array<int, string> */
    public function portionOptions(): array
    {
        return collect(ActivityPortion::cases())->mapWithKeys(fn (ActivityPortion $p) => [$p->value => $p->label()])->all();
    }

    public function start(StartIdp $start): void
    {
        $start->handle($this->user());
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.saved'));
    }

    public function saveIdp(UpdateIdp $update): void
    {
        $update->handle($this->user(), $this->current(), $this->form);
        $this->dispatch('toast', type: 'success', message: __('idp.editor.saved'));
    }

    public function saveItem(int $itemId, SaveIdpItem $save): void
    {
        $this->withErrorPrefix("items.$itemId", fn () => $save->handle($this->user(), $this->current(), $this->items[$itemId] ?? [], $this->findItem($itemId)));
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.item_saved'));
    }

    public function addItem(SaveIdpItem $save): void
    {
        $this->withErrorPrefix('newItem', fn () => $save->handle($this->user(), $this->current(), $this->newItem));
        $this->newItem = [];
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.item_saved'));
    }

    public function removeItem(int $itemId, RemoveIdpItem $remove): void
    {
        $remove->handle($this->user(), $this->current(), $this->findItem($itemId));
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.item_removed'));
    }

    public function addActivity(int $itemId, SaveIdpActivity $save): void
    {
        $this->withErrorPrefix("activity.$itemId", fn () => $save->handle($this->user(), $this->findItem($itemId), $this->activity[$itemId] ?? []));
        unset($this->activity[$itemId]);
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.activity_saved'));
    }

    public function removeActivity(int $activityId, SaveIdpActivity $save): void
    {
        $activity = IdpActivity::query()->whereIn('idp_item_id', $this->current()->items()->select('id'))->findOrFail($activityId);
        $save->remove($this->user(), $activity);
        $this->refreshIdp();
    }

    public function submit(SubmitIdp $submit): void
    {
        $this->resetErrorBag();
        $submit->handle($this->user(), $this->current());
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.submitted'));
    }

    public function publish(PublishLearningPlan $publish): void
    {
        $publish->handle($this->user(), $this->current());
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.published'));
    }

    public function retryPlan(PublishLearningPlan $publish): void
    {
        $publish->retry($this->user(), $this->current());
        $this->dispatch('toast', type: 'success', message: __('idp.editor.published'));
    }

    public function saveCheckin(RecordCheckin $record): void
    {
        $record->handle($this->user(), $this->current(), $this->checkinNote, $this->progress);
        $this->checkinNote = null;
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.checkin_saved'));
    }

    public function saveReflection(CloseIdp $close): void
    {
        $close->reflect($this->user(), $this->current(), $this->reflection);
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('idp.editor.reflection_saved'));
    }

    public function issueReport(IssueLearningReport $issue): void
    {
        $this->resetErrorBag();
        $issue->handle($this->user(), $this->current());
        $this->refreshIdp();
        $this->dispatch('toast', type: 'success', message: __('jp.report.queued'));
    }

    public function render(): View
    {
        return view('idp::livewire.idp-editor')->title(__('idp.editor.title'));
    }

    private function fillForms(): void
    {
        $idp = $this->idp;
        if ($idp === null) {
            return;
        }

        $this->form = ['career_aspiration' => $idp->career_aspiration, 'target_jp' => $idp->target_jp];
        $this->reflection = $idp->closing_reflection;
        $this->items = $idp->items->mapWithKeys(fn (IdpItem $i) => [$i->id => [
            'competency_id' => $i->competency_id,
            'start_level' => $i->start_level,
            'target_level' => $i->target_level,
            'skp_work_result_id' => $i->skp_work_result_id,
            'success_indicator' => $i->success_indicator,
            'mentor_nip' => $i->mentor?->nip,
        ]])->all();
        $this->progress = $idp->items->mapWithKeys(fn (IdpItem $i) => [$i->id => (int) ($idp->checkins->last()?->progress[$i->id] ?? 0)])->all();
    }

    private function refreshIdp(): void
    {
        unset($this->idp, $this->workResultOptions, $this->expectation);
        $this->fillForms();
    }

    /** Pesan validasi Action dipetakan ke kunci isian formulir ini (mis. `start_level` → `items.12.start_level`). */
    private function withErrorPrefix(string $prefix, callable $callback): void
    {
        $this->resetErrorBag();
        try {
            $callback();
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())
                ->mapWithKeys(fn (array $messages, string $key) => [$key === 'items' ? 'items' : $prefix.'.'.$key => $messages])
                ->all());
        }
    }

    private function current(): Idp
    {
        $idp = $this->idp;
        abort_if($idp === null, 404);

        return $idp;
    }

    private function findItem(int $itemId): IdpItem
    {
        return $this->current()->items()->findOrFail($itemId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
