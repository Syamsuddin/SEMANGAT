# 23 — Acceptance Criteria

Fitur aktif = belum ada — MVP F1–F4 terarsip; uji coba 1–2 OPD sebelum F5 (Q-11) (`docs/03_ROADMAP.md`). Patokan selesai = kriteria di sini + DoD (`docs/24_DEFINITION_OF_DONE.md`). Fitur yang sudah diterima dipindah ke `docs/_archive/23-<fitur>.md`. Fase F5+ ditulis di sini saat fasenya dimulai, dari Bab 5 rancang bangun. Setiap fitur ber-UI juga wajib: empat state tampil, layak di 360 px, aksi destruktif lewat dialog konfirmasi (pola: `docs/26_UI_CONVENTIONS.md`).

F1 diterima 2026-10-29 → `docs/_archive/23-F1.md`. F2 diterima 2026-10-30 → `docs/_archive/23-F2.md`. F3 diterima 2026-11-01 → `docs/_archive/23-F3.md`. F4 diterima 2026-11-01 → `docs/_archive/23-F4.md`.

### Blok verifikasi (berlaku untuk tiap fase aktif)
```bash
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && php artisan test --parallel
php artisan test --group=scope
php artisan test --group=arch
```
Lulus: ketiga perintah exit 0; output Pest `Tests: N passed` tanpa `failed`/`skipped` baru; setiap ID kebutuhan fase aktif muncul pada ≥1 nama tes. Gagal: tes merah, tes di-skip, atau ID tanpa tes.
