<?php

use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Integrasi\Models\Employee;

it('FON-09 /verifikasi/{uuid} tanpa login menampilkan jenis, pemilik, tanggal, status berlaku, dan hash tanpa isi', function () {
    $owner = Employee::factory()->create(['name' => 'Siti Contoh']);
    $doc = IssuedDocument::factory()->create(['employee_id' => $owner->id, 'issued_at' => '2026-10-29 10:00:00']);

    $this->assertGuest();
    $this->get('/verifikasi/'.$doc->uuid)
        ->assertOk()
        ->assertSee('Rencana Pembelajaran')
        ->assertSee('Siti Contoh')
        ->assertSee('29 Okt 2026')
        ->assertSee('Dokumen terdaftar dan berlaku.')
        ->assertSee($doc->sha256)
        ->assertDontSee($doc->file_path)
        ->assertDontSee($owner->nip);
})->group('fondasi');

it('FON-09 dokumen dicabut tetap terverifikasi dengan status dicabut', function () {
    $doc = IssuedDocument::factory()->revoked()->create();

    $this->get('/verifikasi/'.$doc->uuid)->assertOk()->assertSee('DICABUT');
})->group('fondasi');

it('FON-09 uuid tidak dikenal atau tidak valid → 404', function () {
    $this->get('/verifikasi/00000000-0000-4000-8000-000000000000')->assertNotFound();
    $this->get('/verifikasi/bukan-uuid')->assertNotFound();
})->group('fondasi');
