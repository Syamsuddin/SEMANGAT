<?php

namespace App\Modules\JpRiwayat\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Berkas sertifikat luar (JPR-02): nama berkas diganti UUID, disimpan di object storage (bukan public/),
 * disajikan hanya sebagai lampiran dengan Content-Type hasil deteksi (docs/21 § Validasi input).
 */
class CertificateStore
{
    public function put(string $contents, string $extension): string
    {
        $path = 'certificates/'.Str::uuid()->toString().'.'.strtolower($extension);
        $this->disk()->put($path, $contents);

        return $path;
    }

    public function get(string $path): string
    {
        return (string) $this->disk()->get($path);
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    /** MIME dari isi berkas, bukan dari nama/ekstensi. */
    public function mimeType(string $contents): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('semangat.jp.certificates.disk'));
    }
}
