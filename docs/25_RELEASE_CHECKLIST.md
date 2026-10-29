# 25 — Release Checklist

Berurutan; satu langkah gagal = berhenti. Rilis produksi adalah operasi irreversibel (`docs/22_CHANGE_POLICY.md`) — butuh "ya" eksplisit pemilik produk.

| # | Langkah | Perintah / bukti | Lulus bila |
|---|---|---|---|
| 1 | Gerbang kode di commit rilis | gerbang pra-commit `docs/11_COMMANDS.md` + `composer audit` + `npm audit --omit=dev` | semua exit 0; tanpa kerentanan high/critical |
| 2 | Tag rilis | `git tag vX.Y.Z` (SemVer) + catatan rilis memuat ID kebutuhan | tag terdorong |
| 3 | Uji migrasi di staging | `php artisan migrate --pretend` lalu `php artisan migrate --force` di staging dari salinan skema produksi (tanpa data asli) | tanpa error; `down()` diuji untuk migrasi non-destruktif |
| 4 | UAT/smoke staging | skenario siklus IDP oleh perwakilan peran; uji 360 px | tidak ada temuan blocker |
| 5 | Backup produksi | dump MySQL + snapshot bucket MinIO, salin ke lokasi terpisah | berkas backup ada & ukuran wajar; uji restore cepat ke DB sementara |
| 6 | Mode pemeliharaan (di luar jam kerja, NFR-03) | `php artisan down --secret=<token>` | halaman pemeliharaan tampil |
| 7 | Deploy | tarik image/tag → `composer install --no-dev -o` → `npm run build` → `php artisan migrate --force` → `php artisan optimize` → `php artisan queue:restart` | semua exit 0 |
| 8 | Naikkan | `php artisan up` | `/up` 200 |
| 9 | Smoke produksi | login tiap peran uji; buka beranda; verifikasi satu QR; panel integrasi; `failed_jobs` = 0 | semua lolos dalam 15 menit |
| 10 | Pantau 24 jam | sinyal `docs/15_OBSERVABILITY.md` | tanpa lonjakan error/antrean |

## Rollback
Gagal di langkah 7–9: `php artisan down` → deploy tag sebelumnya → bila migrasi non-destruktif: `php artisan migrate:rollback --step=<n>`; bila destruktif: pulihkan backup langkah 5 → `php artisan up` → catat insiden.
