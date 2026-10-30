# 23 — Acceptance Criteria

Fitur aktif = MVP F3–F4 (F1–F2 terarsip) (`docs/03_ROADMAP.md`). Patokan selesai = kriteria di sini + DoD (`docs/24_DEFINITION_OF_DONE.md`). Fitur yang sudah diterima dipindah ke `docs/_archive/23-<fitur>.md`. Fase F5+ ditulis di sini saat fasenya dimulai, dari Bab 5 rancang bangun. Setiap fitur ber-UI juga wajib: empat state tampil, layak di 360 px, aksi destruktif lewat dialog konfirmasi (pola: `docs/26_UI_CONVENTIONS.md`).

F1 diterima 2026-10-29 → `docs/_archive/23-F1.md`. F2 diterima 2026-10-30 → `docs/_archive/23-F2.md`.

## F3 — IDP
- **IDP-01** Given pegawai dengan gap & RHK SKP periode berjalan, When membuka "Susun IDP", Then draf berisi ≤3 kompetensi gap terbesar dengan usulan tautan RHK.
- **IDP-02** Given IDP berisi 3 butir, When menambah butir ke-4, Then ditolak "IDP maksimal 3 kompetensi…". Given IDP berisi 1 butir, When diajukan, Then ditolak "Pilih sedikitnya 2 kompetensi prioritas".
- **IDP-10** Given butir tanpa RHK, When diajukan, Then IDP tetap terkirim, butir bertanda "Belum tertaut SKP", dan IDP muncul di daftar "IDP tanpa tautan SKP" atasan/OPD.
- **IDP-04** Given atasan dengan 5 IDP `submitted`, When memilih 5 lalu "Setujui" dengan satu catatan, Then kelimanya `approved`, tiap pegawai menerima notifikasi; dapat diselesaikan dari ponsel 360 px.
- **IDP-05** Given IDP `approved`, When pegawai memilih "Terbitkan Rencana Pembelajaran", Then PDF ber-QR berisi kompetensi prioritas, tautan RHK, aktivitas 10:20:70, target JP terbit, tercatat di `out_issued_documents`, dan teks ekspektasi khusus "Kompeten" sesuai jenjang jabatan tampil dengan tombol "Salin".
- **IDP-06/07** Given IDP `active`, Then check-in triwulanan tersimpan; When ditutup dengan refleksi + penilaian atasan, Then status `closed`.

## F4 — JP & Laporan
- **JPR-01** Given 12 JP `verified` dan 8 JP `pending`, Then saldo tampil "12 dari 20 JP", status "Belum terpenuhi".
- **JPR-02** Given sertifikat dengan hash yang sama sudah ada untuk pegawai itu, When diunggah lagi, Then ditolak "Sertifikat ini sudah pernah diunggah". Given verifikator menolak, Then alasan wajib & terlihat pegawai.
- **JPR-05** Given IDP `closed`, When "Terbitkan Laporan Hasil Pembelajaran", Then PDF ber-QR berisi target & realisasi IDP, rekap JP, bukti, refleksi, catatan atasan.
- **JPR-06** Koordinator OPD melihat rekap JP OPD-nya saja; CLO melihat agregat kabupaten.
- **E2E** Siklus draf → setuju → Rencana → JP → check-in → tutup → Laporan lulus di `CycleTest`.

### Blok verifikasi
```bash
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && php artisan test --parallel
php artisan test --group=scope
php artisan test --group=arch
```
Lulus: ketiga perintah exit 0; output Pest `Tests: N passed` tanpa `failed`/`skipped` baru; setiap ID kebutuhan di atas muncul pada ≥1 nama tes (`grep -rl "IDP-04" tests/` tidak kosong, dst. per ID). Gagal: tes merah, tes di-skip, atau ID tanpa tes.
