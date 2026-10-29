<?php

namespace App\Modules\Integrasi\Http\Controllers;

use App\Modules\Integrasi\Data\SourceRowMapper;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Templat CSV impor pegawai (judul kolom saja, tanpa data). */
class EmployeeTemplateController
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo implode(',', SourceRowMapper::COLUMNS)."\n";
        }, 'templat-impor-pegawai.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
