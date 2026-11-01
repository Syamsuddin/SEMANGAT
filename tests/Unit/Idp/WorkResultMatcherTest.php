<?php

use App\Modules\Idp\Services\WorkResultMatcher;

it('IDP-01 mengusulkan RHK dengan irisan kata terbanyak', function () {
    $rhk = [11 => 'Terlaksananya pelayanan administrasi kepegawaian', 12 => 'Tersusunnya analisis data anggaran daerah', 13 => 'Laporan keuangan tepat waktu'];

    expect((new WorkResultMatcher)->suggest('Analisis data dan anggaran', $rhk))->toBe(12);
})->group('idp');

it('IDP-01 tanpa irisan memakai RHK pertama sebagai usulan umum', function () {
    expect((new WorkResultMatcher)->suggest('Integritas', [21 => 'Laporan bulanan', 22 => 'Rapat koordinasi']))->toBe(21);
})->group('idp');

it('IDP-01 tanpa RHK tidak ada usulan (butir akan ditandai belum tertaut)', function () {
    expect((new WorkResultMatcher)->suggest('Analisis data', []))->toBeNull();
})->group('idp');

it('IDP-01 kata umum dan kata pendek tidak dihitung sebagai irisan', function () {
    $rhk = [31 => 'Penyusunan dokumen dan laporan', 32 => 'Pengelolaan arsip digital'];

    expect((new WorkResultMatcher)->suggest('Pengelolaan dan arsip', $rhk))->toBe(32);
})->group('idp');
