<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Actions\AttachAssessmentReport;
use App\Modules\Kompetensi\Actions\OpenAssessmentReport;
use App\Modules\Kompetensi\Models\Assessment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FON-05 di F2: laporan asesmen rinci. Pemilik langsung terbuka (tercatat); pengelola/auditor wajib alasan.
 * Atasan & peran lain → 403. Catatan/berkas 🔒 hanya dimuat setelah terbuka.
 */
#[Layout('layouts.app')]
class AssessmentDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $assessmentId;

    #[Locked]
    public bool $unlocked = false;

    public string $reason = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(int $assessment, OpenAssessmentReport $open): void
    {
        $this->assessmentId = $assessment;
        $record = $this->assessment();
        $this->authorize('viewDetail', $record);
        if ($record->employee_id === $this->user()->employee_id) {
            $open->open($this->user(), $record);
            $this->unlocked = true;
        }
    }

    public function open(OpenAssessmentReport $open): void
    {
        $open->open($this->user(), $this->assessment(), $this->reason);
        $this->unlocked = true;
        $this->reset('reason');
    }

    public function download(OpenAssessmentReport $open): StreamedResponse
    {
        abort_unless($this->unlocked, 403);

        return $open->download($this->user(), $this->assessment());
    }

    public function upload(AttachAssessmentReport $attach): void
    {
        $this->authorize('attachReport', $this->assessment());
        $this->validate(['file' => ['required', 'file', 'max:'.config('semangat.sensitive.max_kb'), 'extensions:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png']]);
        $attach($this->user(), $this->assessment(), $this->file);
        $this->reset('file');
        $this->dispatch('toast', type: 'success', message: __('kompetensi.detail.uploaded'));
    }

    private function assessment(): Assessment
    {
        return Assessment::query()->visibleTo($this->user())->with(['employee:id,name,nip', 'competency', 'validator:id,name'])->findOrFail($this->assessmentId);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        return view('kompetensi::livewire.assessment-detail', ['assessment' => $this->assessment()])->title(__('kompetensi.detail.title'));
    }
}
