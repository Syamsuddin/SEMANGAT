# Panduan singkat — Integrasi (M02)

- **Pengelola**: menu "Integrasi" → "Impor pegawai" menerima berkas templat XLSX/CSV (unduh templat CSV di halaman yang sama; kolom NIP harus berformat teks). Satu baris tidak valid membuat seluruh berkas ditolak; laporan per baris tampil di panel. Run gagal dapat diulang dengan tombol "Ulangi". Impor berkas tidak menonaktifkan pegawai yang tidak tercantum — status nonaktif diisi lewat kolom `status`.
- **CLO, auditor**: dapat melihat panel kesehatan integrasi (waktu sinkron, jumlah gagal) tanpa tombol ulang.
- Sinkron otomatis harian 02.00 WITA berjalan hanya bila adaptor API server pegawai dikonfigurasi (`SEMANGAT_EMPLOYEE_ADAPTER=simpeg_api`).
- **Pengelola (SKP, F2)**: salinan SKP baca-saja (RHK, ekspektasi khusus, catatan dialog, realisasi) diimpor lewat `php artisan semangat:import skp <berkas> --period=<tahun>`; run tampil di panel integrasi. Kolom nilai/predikat pada berkas diabaikan dan tidak pernah disimpan. Aplikasi tidak mengirim apa pun ke e-Kinerja.
