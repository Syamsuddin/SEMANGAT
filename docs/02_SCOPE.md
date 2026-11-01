# 02 — Scope

Gerbang anti scope-creep. Permintaan yang tidak tercakup di sini → **berhenti & tanya**.

## In-scope
- Aplikasi web SEMANGAT CORPU untuk seluruh ASN Pemkab HSS (PNS & PPPK) dan 9 peran pengelolanya (`docs/05_USER_ROLE.md`).
- 13 modul M01–M13 dengan kebutuhan Bab 5 rancang bangun v0.1 (`docs/01_PRD.md`); urutan pembangunan per `docs/03_ROADMAP.md`.
- Adaptor integrasi masuk: server pegawai lokal (SIASN), e-Kinerja (baca), sistem talenta; keluar: ekspor JP/laporan ke LAN & sistem kepegawaian.
- Dokumen terbitan: Rencana Pembelajaran, Laporan Hasil Pembelajaran, sertifikat, dokumen HCDP, notulen/BA forum, ringkasan strategis 1 halaman — semua PDF ber-QR verifikasi.

## Out-of-scope (eksplisit)
1. **Penilaian kinerja & predikat SKP** — tetap di e-Kinerja. Corpu tidak menghitung, menyimpan, atau menampilkan predikat.
2. **Menulis ke e-Kinerja** dalam bentuk apa pun (API, scraping, RPA). Laporan diunggah pegawai sendiri.
3. **Penyelenggaraan asesmen kompetensi** (assessment center, uji JF) — hanya hasilnya yang dikelola.
4. **Manajemen talenta, seleksi, penempatan jabatan** — posisi kotak talenta hanya dibaca.
5. **Aplikasi mobile native / PWA offline** — cukup web responsif.
6. **Antarmuka multi-bahasa** — Bahasa Indonesia saja.
7. **Fitur AI** (IDP-09 rekomendasi, KMS-06 asisten pencarian) — ditunda sampai ada persetujuan tata kelola & perjanjian pemrosesan data.
8. Kebutuhan berprioritas **DAPAT** (INT-06/07, LMS-08, EVA-09) — hanya atas instruksi eksplisit.
9. Kebijakan pendamping (Perbup/SE Corpu, SK struktur) — disiapkan di luar aplikasi; aplikasi hanya menyimpannya (TKL-01/10).

## Asumsi & batasan
- SSO pemda belum tersedia: login NIP + kata sandi, adaptor SSO disiapkan `[ASUMSI]`.
- API SKP belum pasti: impor berkas terkendali dipakai lebih dulu dengan antarmuka adaptor yang sama (INT-03).
- Data asesmen & SKJ teknis tidak lengkap: sistem tetap berjalan dengan data berlabel indikatif.
- Dipasang di server pemda sendiri (container).

## Keputusan (dari Bab 16 rancang bangun) — ditutup 2026-11-01
Seluruh keputusan terbuka ditutup oleh keputusan pemilik produk 2026-11-01. Rincian teknis dicatat di dokumen pemilik masing-masing.

| ID | Pertanyaan | Keputusan |
|---|---|---|
| Q-01 | Migrasi data/pengguna CORPU lama? | **Tidak dimigrasi** — SEMANGAT CORPU memakai data baru (pegawai dari sinkron/impor, JP dicatat sejak aplikasi berjalan). Tidak ada slice migrasi; risiko R-14 gugur. |
| Q-02 | Data SKP via API? | Impor berkas terkendali tetap jalur utama; API baca-saja diajukan ke pengelola e-Kinerja dan dipasang lewat adaptor `SkpSource` bila tersedia. |
| Q-03 / Q-08 | SK struktur & Perbup/SE Corpu? | Diterbitkan pemda sebelum F7. Sampai itu `pengelola`/`clo`/`dewan_pengarah` lewat penugasan tercatat (`sys_role_assignments`). |
| Q-04 | Evaluasi L3 sudah rutin? | Mulai ringan: ≤ 5 pertanyaan untuk atasan (EVA-04). |
| Q-05 | Kelengkapan SKJ teknis & hasil uji | SKJ manajerial & sosiokultural diimpor lebih dulu, SKJ teknis bertahap; kekosongan tetap tampil berlabel Indikatif. |
| Q-06 | Server pegawai lokal sebagai lapisan data bersama | Impor berkas jalur utama sampai API server pegawai stabil; adaptor `simpeg_api` siap. |
| Q-09 | Format integrasi platform LAN | Ditunda; ekspor CSV/XLSX lebih dulu. |
| Q-10 | Kepanjangan "SEMANGAT" | Tidak menghambat; nama produk tetap "SEMANGAT CORPU" tanpa kepanjangan di antarmuka sampai ditetapkan. |
| Q-11 | Tahapan final | `docs/03_ROADMAP.md` **disetujui**: MVP = F1–F4; uji coba di 1–2 OPD setelah F4 sebelum F5. |
| NFR-12 | Retensi arsip daerah | Audit log disimpan 5 tahun; dicocokkan dengan Jadwal Retensi Arsip daerah sebelum rilis (`docs/07_DATA_MODEL.md` § Retensi). |
| Notifikasi (FON-07) | Kanal notifikasi | **In-app saja** (tabel `notifications`); surel & WhatsApp tidak dibangun. |
| Unggahan | Pemindaian malware | **Tanpa pemindai** (server Linux); cukup pemeriksaan berkas standar (`docs/21_SECURITY_RULES.md` § Validasi input). |
| Penyimpanan objek | Pengganti image MinIO | Abstraksi disk S3 dipertahankan; dev memakai disk lokal; produksi memakai object storage S3-kompatibel infrastruktur pemda, atau SeaweedFS/Garage bila dipasang sendiri (`docs/09_STACK.md`). |
| PDF | Penerbitan dokumen | Mulai F4 `DocumentIssuer` menerbitkan lewat queue `documents` (`docs/08_ARCHITECTURE.md`); Rencana Pembelajaran F3 ikut dipindah. |
| Nilai bawaan | Ambang & tenggat berlabel `[ASUMSI]` | Dipertahankan (n<5, N=3, sinkron 02.00 WITA, L3 T+3 bln +30 hari, sesi idle 30/15 menit) dan dipindah ke `config/semangat.php` saat fasenya disentuh. |
