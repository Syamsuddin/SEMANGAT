<?php

namespace App\Support\Documents;

use App\Modules\Fondasi\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;

/**
 * Isi satu jenis dokumen terbitan: dirakit ulang dari sumbernya saat job `documents` berjalan,
 * sehingga yang diantrikan hanya rujukan model, bukan data (docs/08 A5, keputusan PDF 2026-11-01).
 */
interface DocumentContent
{
    public function type(): DocumentType;

    public function view(): string;

    /** @return array<string, mixed> */
    public function data(Model $documentable): array;

    /** Halaman tempat dokumen dapat diunduh — ditautkan di notifikasi "dokumen siap". */
    public function readyUrl(Model $documentable): string;
}
