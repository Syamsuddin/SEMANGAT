<?php

namespace App\Modules\Kompetensi\Livewire;

use App\Modules\Fondasi\Enums\Permission;
use App\Modules\Fondasi\Models\User;
use App\Modules\Kompetensi\Actions\ImportCompetencyFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** KOM-01/02/03: pengelola mengimpor kamus kompetensi, SKJ, atau hasil uji resmi (XLSX/CSV, ≤10 MB). */
#[Layout('layouts.app')]
class CompetencyImport extends Component
{
    use WithFileUploads;

    public string $kind = ImportCompetencyFile::DICTIONARY;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    /** @var array{rows_ok: int, errors: array<int, list<string>>, error: string|null}|null */
    public ?array $report = null;

    public function mount(): void
    {
        $this->authorize(Permission::CompetencyManage->value);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(ImportCompetencyFile::COLUMNS))],
            'file' => ['required', 'file', 'max:'.config('semangat.imports.max_kb'), 'extensions:csv,xlsx',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream'],
        ];
    }

    public function import(ImportCompetencyFile $import): void
    {
        $this->authorize(Permission::CompetencyManage->value);
        $this->validate();
        /** @var User $user */
        $user = auth()->user();
        $extension = strtolower($this->file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx';
        $path = (string) $this->file->storeAs((string) config('semangat.imports.path'), Str::uuid().'.'.$extension, (string) config('semangat.imports.disk'));

        $result = $import($this->kind, $path, $user);
        $this->report = ['rows_ok' => $result->rowsOk, 'errors' => $result->errors, 'error' => $result->error];
        $this->reset('file');
        if ($result->succeeded()) {
            $this->dispatch('toast', type: 'success', message: __('kompetensi.import.done', ['count' => $result->rowsOk]));
        }
    }

    public function render(): View
    {
        return view('kompetensi::livewire.competency-import', ['columns' => ImportCompetencyFile::COLUMNS[$this->kind] ?? []])
            ->title(__('kompetensi.import.title'));
    }
}
