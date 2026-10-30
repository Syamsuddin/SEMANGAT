<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Actions\OpenAssessmentReport;
use App\Modules\Kompetensi\Actions\RecalculateGaps;
use App\Modules\Kompetensi\Data\GapLine;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\JobStandard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * KOM-03/04/05: SKJ jabatan, level aktual, kesenjangan berlabel keyakinan. Milik sendiri (S) atau pegawai
 * dalam cakupan (T/O/B); di luar cakupan → 404 (FON-04). Tanpa laporan rinci — hanya tautan bagi yang berhak.
 */
#[Layout('layouts.app')]
class CompetencyProfile extends Component
{
    #[Locked]
    public int $employeeId;

    public function mount(?int $employee = null): void
    {
        $this->employeeId = $employee ?? $this->user()->employee_id ?? abort(404);
        $this->employee();
    }

    public function render(RecalculateGaps $gaps, OpenAssessmentReport $reports): View
    {
        $employee = $this->employee();
        $isSelf = $this->user()->employee_id === $employee->id;
        $lines = $gaps->linesFor($employee);

        return view('kompetensi::livewire.competency-profile', [
            'employee' => $employee,
            'isSelf' => $isSelf,
            'standard' => JobStandard::forPosition($employee->position),
            'lines' => $lines,
            'competencies' => $this->competencies($lines),
            'assessments' => $this->assessments(),
            'accessLog' => $isSelf ? $reports->accessLog($employee->id) : collect(),
        ])->title($isSelf ? __('kompetensi.profile.title') : __('kompetensi.profile.title_other', ['name' => $employee->name]));
    }

    private function employee(): Employee
    {
        return Employee::query()->visibleTo($this->user())->with('position')->findOrFail($this->employeeId);
    }

    /**
     * @param  list<GapLine>  $lines
     * @return Collection<int, Competency>
     */
    private function competencies(array $lines): Collection
    {
        return Competency::query()->whereIn('id', array_map(fn (GapLine $l) => $l->competencyId, $lines))->get()->keyBy('id');
    }

    /** @return Collection<int, Assessment> */
    private function assessments(): Collection
    {
        return Assessment::query()->visibleTo($this->user())->select(Assessment::NON_SENSITIVE_COLUMNS)
            ->with('competency:id,code,name')->where('employee_id', $this->employeeId)
            ->latest('assessed_on')->latest('id')->get();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
