<?php

namespace App\Modules\JpRiwayat\Http\Controllers;

use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Services\CertificateStore;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/** JPR-02/04: unduh berkas sertifikat luar — dalam cakupan JpRecordPolicy@view; selalu lampiran, Content-Type dari isi (docs/21). */
class CertificateDownloadController
{
    public function __invoke(JpRecord $record, CertificateStore $files): Response
    {
        Gate::authorize('view', $record);
        $certificate = $record->certificate;
        abort_if($certificate === null || $certificate->is_internal, 404);

        $contents = $files->get($certificate->file_path);

        return response($contents, 200, [
            'Content-Type' => $files->mimeType($contents),
            'Content-Disposition' => 'attachment; filename="sertifikat-'.$record->id.'.'.pathinfo($certificate->file_path, PATHINFO_EXTENSION).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
