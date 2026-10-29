# 16 — Debugging Guide

Lokasi log & format: `docs/15_OBSERVABILITY.md`. Perintah: `docs/11_COMMANDS.md`.

## Gejala → diagnosa
| Gejala | Cek pertama |
|---|---|
| Atasan tidak melihat timnya | `hr_employees.supervisor_id` bawahan; sinkron terakhir di `int_sync_runs`; ada `gov_role_overrides` kedaluwarsa? cache izin spatie (`php artisan permission:cache-reset`) |
| Kesenjangan tidak berubah setelah hasil uji baru | event `AssessmentRecorded` terkirim? worker queue jalan? `valid_until` sudah lewat? asesmen mandiri belum `validated_at`? |
| Saldo JP tidak bertambah | status `out_jp_records` masih `pending`? listener `RecordJp` gagal di `failed_jobs`? |
| PDF tidak terbit | worker queue `documents` jalan? `failed_jobs`; koneksi MinIO (`AWS_ENDPOINT`, path-style) |
| QR verifikasi 404 | `SEMANGAT_VERIFY_BASE_URL` salah; `out_issued_documents.uuid` ada? |
| Impor SKP ditolak | unduh laporan per baris; kolom wajib & format periode di `tests/Fixtures/skp/` sebagai acuan |
| Tes scope gagal setelah fitur baru | query baru tidak memakai `visibleTo($user)` |
| Livewire "component not found" | ServiceProvider modul belum mendaftarkan namespace komponen |

## Jebakan proyek (landmines)
1. **e-Kinerja hanya dibaca.** `SkpSource` sengaja tanpa metode tulis. Jangan "melengkapi" dengan `push`/`update` — itu melanggar kesepakatan D-04 dan out-of-scope.
2. **Sumber menang.** Data ⇅ (pegawai, jabatan, SKP, talenta) tidak boleh diedit di Corpu; sinkron berikut akan menimpanya. Koreksi = di sumber, atau `gov_role_overrides` berkedaluwarsa untuk peran.
3. **Peran atasan diturunkan.** Jangan `assignRole('atasan')` manual; peran dihitung dari `supervisor_id` saat sinkron.
4. **Laporan asesmen rinci ≠ ringkasan gap.** Atasan boleh melihat gap tim, tidak boleh melihat `cmp_assessments.report_file_path`/`notes`.
5. **Kolom 🔒 tidak bisa di-`where`/`orderBy`.** Cari via kolom non-terenkripsi; jangan mendekripsi massal di query.
6. **Indikatif bukan nol.** Pegawai tanpa hasil uji tetap punya gap `indicative` = level SKJ; jangan tampilkan "tidak ada kesenjangan".
7. **Data evaluasi individu hanya untuk pengembangan.** Jangan pernah menggabungkannya ke ekspor/laporan yang menyangkut kinerja.
8. **Zona waktu WITA** (`Asia/Makassar`): tenggat T+3 bulan dan jadwal forum dihitung di zona ini.
