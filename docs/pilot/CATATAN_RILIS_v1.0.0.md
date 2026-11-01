# Catatan rilis SEMANGAT CORPU v1.0.0 (DRAF) — MVP F1–F4 untuk uji coba OPD

Status: **draf, belum ditag**. Tag `v1.0.0` dibuat setelah PR #4 (F4) digabung ke `main` dan blocker di bawah selesai. Rilis mengikuti `docs/25_RELEASE_CHECKLIST.md` secara berurutan dan butuh persetujuan eksplisit pemilik produk (`docs/22_CHANGE_POLICY.md`).

## Blocker sebelum uji coba
| # | Blocker | Dampak | Keputusan yang dibutuhkan |
|---|---|---|---|
| B-1 | **Aktivasi akun belum dibangun.** Keputusan pemilik produk 2026-11-01 (`docs/21_SECURITY_RULES.md` § Autentikasi): pengelola memicu aktivasi, pegawai menerima tautan sekali pakai, wajib ganti kata sandi saat pertama masuk. Saat ini akun hasil sinkron diberi kata sandi acak yang tidak diketahui siapa pun, dan reset mandiri dinonaktifkan. | Pegawai OPD pilot **tidak dapat masuk**. | Kerjakan sebagai slice kecil sebelum pilot (perubahan keamanan, gerbang manusia), atau tetapkan prosedur sementara yang tertulis. |

## Isi rilis (ID kebutuhan)
- **F1 Fondasi & data pegawai**: FON-01 masuk dengan NIP, FON-03 peran atasan otomatis dari data jabatan, FON-04 cakupan data 9 peran, FON-05 jejak audit pembukaan data sensitif, FON-06 2FA wajib untuk peran pengelola, FON-09 verifikasi QR publik `/verifikasi/{uuid}`, INT-01/03 sinkron dan impor berkas pegawai, INT-08 panel kesehatan integrasi.
- **F2 Profil & kesenjangan**: KOM-01…06 dan KOM-08 (SKJ, kamus, hasil uji, mesin kesenjangan berlabel keyakinan, asesmen mandiri yang divalidasi atasan, agregat OPD/kabupaten), INT-02 impor berkas SKP (baca saja).
- **F3 IDP**: IDP-01…07 dan IDP-10 (draf otomatis, 2–3 kompetensi, tautan RHK, persetujuan massal, Rencana Pembelajaran PDF ber-QR, check-in, penutupan, penanda "Belum tertaut SKP").
- **F4 JP & Laporan**: JPR-01…06 (saldo 20 JP, unggah dan verifikasi sertifikat luar, riwayat bangkom, Laporan Hasil Pembelajaran PDF ber-QR, rekap OPD/kabupaten), FON-07 dasar (notifikasi in-app).

Batasan yang disengaja: tidak ada penulisan ke e-Kinerja, tidak ada nilai/predikat SKP, notifikasi hanya di dalam aplikasi, katalog/LMS/evaluasi menyusul di F5–F6.

## Perubahan yang perlu diperhatikan tim server
- **Worker antrean wajib berjalan** (sejak F4, semua PDF dibuat di antrean `documents`): `php artisan queue:work redis --queue=default,documents,sync`. Tanpa worker, Rencana Pembelajaran dan Laporan tidak pernah terbit.
- **Scheduler** untuk sinkron pegawai harian 02.00 WITA bila adaptor `simpeg_api` dipakai: `php artisan schedule:work` (atau cron `schedule:run`).
- **Migrasi baru (non-destruktif)**: tabel `hr_*`, `int_*`, `sys_*`, `cmp_*`, `pln_idp*`, `out_issued_documents`, `out_jp_records`, `out_certificates`, `notifications`. Uji `migrate --pretend` dan `down()` di staging (langkah #3).
- **Izin peran**: setelah migrasi jalankan `php artisan db:seed --class="App\Modules\Fondasi\Database\Seeders\RolePermissionSeeder"` (idempoten, aman di produksi) agar izin F4 `jp.verify` dan `jp.view_recap` terpasang.
- **Penyimpanan berkas**: disk dokumen (`SEMANGAT_DOCUMENT_DISK`), sertifikat (`SEMANGAT_CERTIFICATE_DISK`, bawaan sama dengan disk dokumen), dan berkas sensitif terenkripsi (`SEMANGAT_SENSITIVE_DISK`, bucket terpisah). Semua di object storage, bukan `public/`.
- **`SEMANGAT_VERIFY_BASE_URL`** harus berisi alamat publik yang dapat dijangkau pemindai QR.

## Bukti gerbang kode (commit F4)
`pint --test` lulus · `phpstan analyse` 0 galat · `php artisan test --parallel` 285 passed tanpa yang gagal atau di-skip · `--group=scope` 78 passed · `--group=arch` 14 passed · `composer audit`: tidak ada advisori · `npm audit --omit=dev`: 0 kerentanan. Jalankan ulang di commit rilis sebelum tag (langkah #1).

## Yang belum diverifikasi
- Tampilan di ponsel 360 px belum diperiksa secara visual di peramban. Dicek dalam UAT (`docs/pilot/SKENARIO_UAT.md`).
- Kinerja (P95 ≤ 2 dtk) dan aksesibilitas otomatis (axe-core) dijadwalkan pra-rilis (`docs/13_TESTING.md`).
