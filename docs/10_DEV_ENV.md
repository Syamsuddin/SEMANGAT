# 10 — Dev Environment

Perintah aktual: `docs/11_COMMANDS.md`.

## Prasyarat
Docker Desktop/Engine + Compose v2, Git. (Tanpa Docker: PHP 8.3 + ekstensi `pdo_mysql, redis, gd, intl, zip, bcmath`, Composer 2, Node 20, MySQL 8, Redis 7, MinIO.)

## Layanan Compose (`docker-compose.yml`, basis Laravel Sail; prefix perintah `./vendor/bin/sail`)
| Layanan | Port lokal | Catatan |
|---|---|---|
| `laravel.test` (runtime Sail PHP 8.3) | 8000 (`APP_PORT`) | kode di-mount; produksi tetap PHP-FPM + Nginx |
| `mysql` (8.0) | 3306 | DB `semangat`; DB tes `semangat_test` dibuat `docker/mysql/create-test-database.sh` (hak `semangat_test%` untuk `--parallel`) |
| `redis` | 6379 | queue/cache/session |
| `minio` | 9000 / konsol 9001 | **belum ada di compose** — image `minio/minio` tidak tersedia di Docker Hub/quay (Okt 2026); pengganti S3-kompatibel menunggu keputusan (`docs/22`). Sementara dev: `FILESYSTEM_DISK=local`; tes memakai `Storage::fake` |
| `mailpit` | 8025 | tangkap surel dev |

Catatan Codespaces/Docker-in-Docker: bila TCP antar-container di jaringan bridge diblokir, isi `DB_HOST`, `REDIS_HOST`, `MAIL_HOST` = `host.docker.internal` di `.env` lokal (port dipublikasikan ke host). Container Sail juga dapat tanpa akses internet keluar — jalankan Composer lewat container sekali-pakai `laravelsail/php83-composer`, npm lewat `node:20-alpine`.

## Langkah setup lokal
1. `cp .env.example .env` → isi variabel di bawah.
2. Jalankan urutan "Setup pertama" di `docs/11_COMMANDS.md`.
3. Masuk sebagai akun seed (lihat `database/seeders/DevUserSeeder.php`; satu akun per peran, NIP fiktif berawalan `1999`).
4. **Tidak ada data produksi asli di lingkungan dev/uji** (Bab 10.2). Data contoh hanya dari factory/seeder.

## Variabel `.env` (nama saja)
`APP_NAME, APP_ENV, APP_KEY, APP_URL, APP_TIMEZONE` (=`Asia/Makassar`), `APP_LOCALE` (=`id`), `APP_FALLBACK_LOCALE` (=`en`, kunci validasi yang belum diterjemahkan) ·
`DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD` ·
`REDIS_HOST, REDIS_PORT, QUEUE_CONNECTION` (=`redis`), `CACHE_STORE, SESSION_DRIVER` ·
`FILESYSTEM_DISK` (=`s3`), `AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, AWS_ENDPOINT, AWS_USE_PATH_STYLE_ENDPOINT` ·
`MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_FROM_ADDRESS` ·
`SEMANGAT_EMPLOYEE_ADAPTER` (`file`|`simpeg_api`), `SIMPEG_API_URL, SIMPEG_API_TOKEN` ·
`SEMANGAT_SKP_ADAPTER` (`file`|`ekinerja_api`), `EKINERJA_API_URL, EKINERJA_API_TOKEN` ·
`SEMANGAT_WA_GATEWAY_URL, SEMANGAT_WA_GATEWAY_TOKEN` (opsional, FON-07) ·
`SEMANGAT_VERIFY_BASE_URL` (URL publik untuk QR) · `SEMANGAT_IMPORT_DISK` (disk berkas impor, bawaan `local`).
