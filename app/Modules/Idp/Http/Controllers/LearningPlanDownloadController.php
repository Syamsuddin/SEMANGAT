<?php

namespace App\Modules\Idp\Http\Controllers;

use App\Modules\Idp\Models\Idp;
use App\Support\DocumentIssuer;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/** IDP-05: unduh Rencana Pembelajaran terbitan — pemilik, atasan, koord. OPD, pengelola/auditor (IdpPolicy@view). */
class LearningPlanDownloadController
{
    public function __invoke(Idp $idp, DocumentIssuer $issuer): Response
    {
        Gate::authorize('view', $idp);
        $document = $idp->learningPlan;
        abort_if($document === null, 404);

        return response($issuer->contents($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rencana-pembelajaran-'.$idp->period_year.'-'.$idp->employee_id.'.pdf"',
        ]);
    }
}
