# 11 — Commands

Pemilik semua perintah. Dokumen lain merujuk ke sini. Prefix `./vendor/bin/sail` bila memakai Sail; tabel ditulis tanpa prefix.

| Tujuan | Perintah |
|---|---|
| Setup pertama | `docker compose up -d` → `composer install` → `npm ci` → `php artisan key:generate` → `php artisan migrate --seed` → `npm run build` |
| Jalankan dev (server + queue + vite) | `composer run dev` |
| Jalankan worker queue saja | `php artisan queue:work redis --queue=default,documents,sync` |
| Scheduler (dev) | `php artisan schedule:work` |
| Tes semua | `php artisan test` |
| Tes satu fitur | `php artisan test --filter=<NamaTes>` (mis. `--filter=IdpItemLimitTest`) |
| Tes per kelompok | `php artisan test --group=<modul>` (mis. `--group=idp`, `--group=scope`, `--group=arch`) |
| Tes paralel + cakupan | `php artisan test --parallel --coverage --min=80` |
| Analisis statis | `./vendor/bin/phpstan analyse` |
| Format (cek / perbaiki) | `./vendor/bin/pint --test` / `./vendor/bin/pint` |
| Buat migrasi | `php artisan make:migration <nama> --path=app/Modules/<Modul>/Database/Migrations` |
| Migrasi ⚠️ | `php artisan migrate` (produksi: `php artisan migrate --force` hanya dalam langkah rilis `docs/25_RELEASE_CHECKLIST.md`) |
| Lihat rencana migrasi | `php artisan migrate --pretend` |
| Reset DB dev ⚠️ dev saja | `php artisan migrate:fresh --seed` |
| Sinkron pegawai manual | `php artisan semangat:sync employees` |
| Impor berkas SKP | `php artisan semangat:import skp <path.xlsx> --period=2027` |
| Impor data kompetensi (kamus/SKJ/hasil uji) | `php artisan semangat:import kamus\|skj\|hasil-uji <path.xlsx> --by=<username pengelola>` |
| Hitung ulang kesenjangan | `php artisan semangat:gaps:recalculate [--employee=<nip>]` |
| Build aset produksi | `npm run build` |
| Cache produksi | `php artisan optimize` |
| Gerbang pra-commit (lokal) | `./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && php artisan test --parallel` |
| Validasi blueprint | `bash scripts/validate.sh` |

Perintah `semangat:*` dibuat pada fase modulnya (F1/F2); bila belum ada, buat sesuai tabel ini, jangan mengganti namanya. Target `kamus|skj|hasil-uji` ditambahkan di F2 (keputusan pemilik produk 2026-10-30: data kompetensi masuk lewat impor berkas, halaman `/kompetensi/impor` setara).
