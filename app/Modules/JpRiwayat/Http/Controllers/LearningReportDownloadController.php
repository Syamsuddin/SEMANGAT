<?php

namespace App\Modules\JpRiwayat\Http\Controllers;

use App\Modules\Idp\Models\Idp;
use App\Support\DocumentIssuer;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/** JPR-05: unduh Laporan Hasil Pembelajaran terbitan — cakupan sama dengan IDP-nya (IdpPolicy@view). */
class LearningReportDownloadController
{
    public function __invoke(Idp $idp, DocumentIssuer $issuer): Response
    {
        Gate::authorize('view', $idp);
        $document = $idp->learningReport;
        abort_if($document === null, 404);

        return response($issuer->contents($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-hasil-pembelajaran-'.$idp->period_year.'-'.$idp->employee_id.'.pdf"',
        ]);
    }
}
