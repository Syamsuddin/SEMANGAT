<?php

namespace App\Modules\Integrasi\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Actions\StartEmployeeImport;
use App\Modules\Integrasi\Data\SourceRowMapper;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** INT-03: impor berkas templat pegawai (XLSX/CSV, ≤10 MB). */
#[Layout('layouts.app')]
class EmployeeImport extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(): void
    {
        $this->authorize(Permission::IntegrationManage->value);
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return ['file' => [
            'required', 'file', 'max:'.config('semangat.imports.max_kb'), 'extensions:csv,xlsx',
            'mimetypes:text/csv,text/plain,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream',
        ]];
    }

    public function import(StartEmployeeImport $start): void
    {
        $this->authorize(Permission::IntegrationManage->value);
        $this->validate();
        /** @var User $user */
        $user = auth()->user();
        $start($this->file, $user);
        $this->reset('file');

        session()->flash('toast', ['type' => 'success', 'message' => __('integrasi.import.queued')]);
        $this->redirectRoute('integrasi.panel', navigate: false);
    }

    public function render(): View
    {
        return view('integrasi::livewire.employee-import', [
            'columns' => SourceRowMapper::COLUMNS,
        ])->title(__('integrasi.import.title'));
    }
}
