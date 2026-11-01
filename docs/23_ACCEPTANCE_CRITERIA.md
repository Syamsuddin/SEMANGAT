# 23 — Acceptance Criteria

Fitur aktif = MVP F4 (F1–F3 terarsip) (`docs/03_ROADMAP.md`). Patokan selesai = kriteria di sini + DoD (`docs/24_DEFINITION_OF_DONE.md`). Fitur yang sudah diterima dipindah ke `docs/_archive/23-<fitur>.md`. Fase F5+ ditulis di sini saat fasenya dimulai, dari Bab 5 rancang bangun. Setiap fitur ber-UI juga wajib: empat state tampil, layak di 360 px, aksi destruktif lewat dialog konfirmasi (pola: `docs/26_UI_CONVENTIONS.md`).

F1 diterima 2026-10-29 → `docs/_archive/23-F1.md`. F2 diterima 2026-10-30 → `docs/_archive/23-F2.md`. F3 diterima 2026-11-01 → `docs/_archive/23-F3.md`.

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
