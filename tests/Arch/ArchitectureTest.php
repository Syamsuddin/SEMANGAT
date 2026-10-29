<?php

// docs/13 § Arsitektur. Tanpa boot aplikasi: path dihitung dari lokasi berkas.

const ROOT = __DIR__.'/../..';

/** @return iterable<SplFileInfo> */
function phpFiles(string $dir): iterable
{
    return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
}

arch('tidak ada dd/dump/ray/var_dump')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed()
    ->group('arch');

arch('Controller & Livewire tidak memakai DB:: langsung')
    ->expect(['App\Modules\Fondasi\Livewire', 'App\Modules\Fondasi\Http', 'App\Modules\Integrasi\Livewire', 'App\Modules\Integrasi\Http'])
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->group('arch');

arch('hanya Integrasi\Adapters yang memanggil sistem luar via Http')
    ->expect('App')
    ->not->toUse('Illuminate\Support\Facades\Http')
    ->ignoring('App\Modules\Integrasi\Adapters')
    ->group('arch');

arch('Contract sumber hanya antarmuka')
    ->expect('App\Modules\Integrasi\Contracts')
    ->toBeInterfaces()
    ->group('arch');

arch('Service murni tidak memakai Http')
    ->expect('App\Modules\Integrasi\Services')
    ->not->toUse('Illuminate\Support\Facades\Http')
    ->group('arch');

arch('Data adalah DTO tanpa Eloquent')
    ->expect('App\Modules\Integrasi\Data')
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->group('arch');

test('peran spatie hanya ditetapkan oleh SyncUserRoles (FON-03)', function () {
    $offenders = [];
    foreach (phpFiles(ROOT.'/app') as $file) {
        if ($file->getExtension() !== 'php' || str_ends_with($file->getPathname(), 'Actions/SyncUserRoles.php')) {
            continue;
        }
        if (preg_match('/->(assignRole|syncRoles|removeRole|givePermissionTo)\(/', (string) file_get_contents($file->getPathname()))) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
})->group('arch');

test('tidak ada withoutGlobalScopes di kode aplikasi (docs/20 #5)', function () {
    $hits = [];
    foreach (phpFiles(ROOT.'/app') as $file) {
        if ($file->getExtension() === 'php' && str_contains((string) file_get_contents($file->getPathname()), 'withoutGlobalScope')) {
            $hits[] = $file->getPathname();
        }
    }

    expect($hits)->toBe([]);
})->group('arch');

test('Blade tidak memakai keluaran mentah {!! !!} (docs/21)', function () {
    $hits = [];
    foreach ([ROOT.'/resources/views', ROOT.'/app/Modules'] as $dir) {
        foreach (phpFiles($dir) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php') && str_contains((string) file_get_contents($file->getPathname()), '{!!')) {
                $hits[] = $file->getPathname();
            }
        }
    }

    expect($hits)->toBe([]);
})->group('arch');
