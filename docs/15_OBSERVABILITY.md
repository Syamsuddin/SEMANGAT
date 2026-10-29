# 15 — Observability

Pemilik logging & pemantauan.

## Log
- Channel `stack` → `daily` (`storage/logs/laravel-*.log`, simpan 30 hari) + `stderr` JSON di container produksi (dikumpulkan log terpusat pemda `[ASUMSI]`).
- Format JSON; konteks wajib: `request_id`, `user_id`, `module`, `action`. Middleware `AssignRequestId` mengisi `X-Request-Id`.
- Level: `debug` (dev saja) · `info` alur bisnis penting (IDP disetujui, dokumen terbit, sinkron selesai) · `warning` akses ditolak, retry · `error` integrasi/job gagal · `critical` 5xx.
- **Dilarang di log**: nilai kolom 🔒, isi catatan coaching, kata sandi, token, isi berkas. NIP boleh (identifikasi), nama tidak perlu.
- Jejak audit **bukan** log aplikasi: ia hidup di `sys_audit_logs` (`docs/07_DATA_MODEL.md`) dan tidak dirotasi.

## Pemantauan
| Sinyal | Sumber | Ambang tindak |
|---|---|---|
| Kesehatan integrasi (INT-08) | `int_sync_runs`, halaman `/pengelola/integrasi` | run terakhir `failed` atau > 26 jam sejak sukses |
| Antrean | `php artisan queue:monitor redis:default,redis:documents,redis:sync --max=100` (terjadwal tiap 5 menit) | > 100 job |
| Job gagal | `failed_jobs` | > 0 → notifikasi admin teknis |
| Ketersediaan | uptime check eksternal ke `/up` (Laravel health route) | NFR-03 99,5% jam kerja |
| Waktu respons | log durasi request (middleware) | P95 > 2 dtk (NFR-01) |
| Backup | log skrip backup harian | gagal 1× → notifikasi |
Dashboard metrik (Prometheus/Grafana) `[TERBUKA]` — tergantung infrastruktur pemda.
