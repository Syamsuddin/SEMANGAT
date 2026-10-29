<?php

namespace App\Modules\Integrasi\Adapters;

use App\Modules\Integrasi\Contracts\EmployeeSource;
use App\Modules\Integrasi\Data\SourceRowMapper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * INT-01: tarik pegawai dari server pegawai lokal (SIASN) — hanya GET.
 * Bentuk respons [ASUMSI] menunggu Q-06: GET {url}/pegawai?page=N →
 * {"data": [{<kolom templat>}...], "meta": {"last_page": N}}.
 */
class SimpegApiAdapter implements EmployeeSource
{
    public const NAME = 'simpeg_api';

    public function name(): string
    {
        return self::NAME;
    }

    public function isFullSnapshot(): bool
    {
        return true;
    }

    public function rejectsWholeBatchOnError(): bool
    {
        return false;
    }

    public function fetch(?string $sourceRef = null): iterable
    {
        $url = rtrim((string) config('semangat.simpeg.url'), '/');
        if ($url === '') {
            throw new RuntimeException(__('integrasi.api.not_configured'));
        }

        $page = 1;
        $rowNumber = 0;
        do {
            $response = Http::withToken((string) config('semangat.simpeg.token'))
                ->acceptJson()
                ->timeout(30)
                ->retry(3, 1000)
                ->get($url.'/pegawai', ['page' => $page])
                ->throw();

            /** @var list<array<string, mixed>> $rows */
            $rows = $response->json('data', []);
            foreach ($rows as $row) {
                yield SourceRowMapper::map(++$rowNumber, $row);
            }

            $lastPage = (int) $response->json('meta.last_page', $page);
            $page++;
        } while ($page <= $lastPage);
    }
}
