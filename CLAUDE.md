# CLAUDE.md

File ini selalu aktif. Untuk hal di luar ini → buka INDEX.md.

## Proyek
SEMANGAT CORPU — sistem pembelajaran terintegrasi (Corporate University, Perlan 6/2023) bagi ASN Pemkab Hulu Sungai Selatan: profil kompetensi → IDP/HCDP → belajar 10:20:70 → JP → evaluasi dampak. Detail: docs/00_EXECUTIVE_SUMMARY.md. Scope: docs/02_SCOPE.md. Teks 102 kebutuhan (`FON-01`…`EVA-10`): docs/_sumber/KEBUTUHAN.md.

## Prinsip kerja agen (non-negotiable)
1. Friksi sebanding irreversibilitas.
2. Lapisan deterministik (validasi, constraint, tes) di bawah penalaran.
3. Human-in-the-loop untuk high-stakes (migrasi DB, hapus data, keamanan, rilis).
4. Jangan refactor keputusan yang disengaja diam-diam.
5. Hormati scope (docs/02). Out-of-scope → berhenti & tanya.

## Stack  (sumber: docs/09)
Laravel 12 · PHP 8.3 · Livewire 3 + Alpine · Tailwind 4 · MySQL 8 · Redis · MinIO · Pest — Terlarang: jQuery, Bootstrap, SPA/Inertia, nwidart, fitur khusus PostgreSQL, layanan AI/analytics eksternal.

## Struktur & konvensi  (sumber: docs/12)
Modul di `app/Modules/<Modul>` (13 modul). Logika hanya di Actions/Services; Livewire/Controller tipis. Query data pegawai selalu `visibleTo($user)`. Tabel ber-prefix domain (`hr_ cmp_ pln_ lrn_ out_ gov_ sys_`); skema: docs/07. Nama tes memuat ID kebutuhan.

## Perintah penting  (sumber: docs/11)
dev: `composer run dev` · test: `php artisan test` · gerbang: `./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && php artisan test --parallel` · migrasi ⚠️: `php artisan migrate`

## Guardrail inti  (penuh: docs/20, 21, 22)
JANGAN menulis apa pun ke e-Kinerja · JANGAN menyimpan/menampilkan nilai atau predikat SKP · JANGAN mengedit data hasil sinkron (sumber menang) atau memberi peran turunan secara manual · JANGAN menampilkan data indikatif/sementara bergaya terverifikasi · Data Tinggi (hasil uji, gap, catatan coaching): enkripsi + audit baca · Evaluasi individu hanya untuk pengembangan · Migrasi destruktif, ubah akses/enkripsi, rilis = gerbang manusia.

## Antarmuka  (sumber: docs/26_UI_CONVENTIONS.md)
Identitas rancang bangun (navy `#0F2A4A`, oranye, teal, Inter) di Tailwind 4, mobile-first 360 px. Hanya token & komponen dari 26; tanpa gradien/emoji/pustaka UI baru; label keyakinan dibedakan pola + ikon, bukan warna saja.

## Alur per-task  (penuh: docs/17, 19)
Baca INDEX.md → muat dokumen relevan → konfirmasi scope → vertical slice → tes (docs/13) → DoD (docs/24).

## Definisi selesai (ringkas; penuh: docs/24)
Kriteria docs/23 terpenuhi · gerbang hijau tanpa skip baru · uji scope 9 peran lulus · audit data sensitif · layak 360 px · fakta blueprint berubah → `bash scripts/validate.sh`.

## Untuk apa pun di luar ini → INDEX.md
