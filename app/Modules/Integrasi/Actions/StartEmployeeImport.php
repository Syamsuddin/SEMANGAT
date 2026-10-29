<?php

namespace App\Modules\Integrasi\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Adapters\EmployeeFileImportAdapter;
use App\Modules\Integrasi\Jobs\SyncEmployees;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** Simpan berkas unggahan (nama diganti UUID, di luar public/) lalu antrekan impor. */
class StartEmployeeImport
{
    public function __invoke(UploadedFile $file, User $by): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx';
        $path = $file->storeAs(
            (string) config('semangat.imports.path'),
            Str::uuid()->toString().'.'.$extension,
            (string) config('semangat.imports.disk'),
        );

        SyncEmployees::dispatch(EmployeeFileImportAdapter::NAME, (string) $path, $by->id);

        return (string) $path;
    }
}
