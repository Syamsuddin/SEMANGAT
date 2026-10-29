<?php

namespace App\Modules\Fondasi\Http\Controllers;

use App\Modules\Fondasi\Models\IssuedDocument;
use Illuminate\Contracts\View\View;

/** FON-09: verifikasi publik tanpa login — hanya jenis, pemilik, tanggal, status, hash; tanpa isi. */
class DocumentVerificationController
{
    public function __invoke(string $uuid): View
    {
        abort_unless(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1, 404);

        $document = IssuedDocument::query()
            ->select(['id', 'uuid', 'type', 'employee_id', 'sha256', 'issued_at', 'revoked_at'])
            ->with('employee:id,name')
            ->where('uuid', strtolower($uuid))
            ->firstOrFail();

        return view('fondasi::verify', ['document' => $document]);
    }
}
