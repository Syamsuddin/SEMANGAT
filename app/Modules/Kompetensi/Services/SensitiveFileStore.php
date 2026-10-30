<?php

namespace App\Modules\Kompetensi\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Berkas laporan asesmen rinci (psikogram) — dienkripsi aplikasi (APP_KEY) sebelum disimpan di disk
 * `semangat.sensitive.disk` (bucket terpisah semangat-sensitive di produksi, docs/21). Nama berkas = UUID.
 */
class SensitiveFileStore
{
    public function put(string $contents, string $extension): string
    {
        $path = 'assessments/'.Str::uuid()->toString().'.'.strtolower($extension).'.enc';
        $this->disk()->put($path, Crypt::encryptString(base64_encode($contents)));

        return $path;
    }

    public function get(string $path): string
    {
        return (string) base64_decode(Crypt::decryptString((string) $this->disk()->get($path)), true);
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    /** Ekstensi asli dari path tersimpan (`….pdf.enc` → `pdf`). */
    public function extension(string $path): string
    {
        return pathinfo(Str::beforeLast($path, '.enc'), PATHINFO_EXTENSION);
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('semangat.sensitive.disk'));
    }
}
