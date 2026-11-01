# 09 — Stack

Pemilik daftar teknologi & versi. Pin versi minor di `composer.json`/`package.json`; menambah paket = lewat `docs/22_CHANGE_POLICY.md`.

| Lapisan | Teknologi | Versi |
|---|---|---|
| Bahasa | PHP | 8.3 |
| Framework | Laravel | 12.x |
| UI reaktif | Livewire | 3.x |
| JS ringan | Alpine.js | 3.x (bawaan Livewire) |
| CSS | Tailwind CSS (via Vite plugin) | 4.x |
| Bundler | Vite | 6.x, Node 20 LTS |
| Basis data | MySQL | 8.0 (kompatibel MariaDB 10.11) |
| Queue/cache/session | Redis | 7.x |
| Penyimpanan objek | S3-kompatibel via `league/flysystem-aws-s3-v3` — produksi: object storage infrastruktur pemda, atau SeaweedFS/Garage bila dipasang sendiri (image MinIO tak tersedia; keputusan pemilik produk 2026-11-01); dev: disk lokal | terbaru stabil |
| RBAC | `spatie/laravel-permission` | 6.x |
| Auth & 2FA | Laravel Fortify | 1.x |
| PDF | `barryvdh/laravel-dompdf` | 3.x |
| QR | `bacon/bacon-qr-code` (PNG via `GDLibRenderer`, butuh ext-gd; sudah dipakai Fortify — `simple-qrcode` 4.x bentrok dengan bacon ^3, keputusan pemilik produk 2026-11-01) | 3.x |
| Impor/ekspor berkas | `openspout/openspout` | 4.x |
| Tes | Pest | 3.x (+ `pest-plugin-laravel`, `pest-plugin-livewire`, arch) |
| Analisis statis | Larastan | 3.x, level 6 |
| Format | Laravel Pint | preset `laravel` |
| Dev lokal | Docker Compose / Laravel Sail; Mailpit | — |
| Ikon | Heroicons (outline 24, solid 20) via `blade-ui-kit/blade-heroicons` | 2.x |

## Teknologi terlarang
| Terlarang | Alasan |
|---|---|
| jQuery, Bootstrap, pustaka komponen UI lain | token & komponen hanya dari `docs/26_UI_CONVENTIONS.md` |
| Vue/React/Inertia/SPA | keputusan A2 (`docs/08_ARCHITECTURE.md`) |
| `nwidart/laravel-modules` atau paket modul lain | struktur modul sendiri (`docs/12_PROJECT_STRUCTURE.md`) |
| Fitur khusus PostgreSQL (JSONB operator, schema) | target MySQL/MariaDB |
| Layanan AI/LLM eksternal, analytics pihak ketiga, CDN font eksternal | data pribadi tidak boleh keluar tanpa persetujuan tata kelola; font Inter di-host sendiri |
| Klien HTTP/SDK yang menulis ke e-Kinerja | out-of-scope mutlak |
