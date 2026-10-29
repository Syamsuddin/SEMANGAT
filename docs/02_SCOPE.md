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

## Keputusan terbuka (dari Bab 16 rancang bangun) `[TERBUKA]`
| ID | Pertanyaan | Dampak ke blueprint |
|---|---|---|
| Q-01 | Migrasi data/pengguna CORPU lama? | Tambah slice migrasi; risiko R-14 |
| Q-02 | Data SKP via API? | Ganti adaptor `SkpFileImportAdapter` → API |
| Q-03 / Q-08 | SK struktur & Perbup/SE Corpu sudah ada? | Isi awal registri M04 |
| Q-04 | Evaluasi L3 sudah rutin? | Seberapa ringan fitur EVA-04 awal |
| Q-05 | Kelengkapan SKJ teknis & hasil uji | Proporsi data indikatif |
| Q-06 | Kesiapan server pegawai lokal sebagai lapisan data bersama | Lokasi domain kepegawaian/kompetensi |
| Q-09 | Format integrasi platform LAN | Format ekspor INT-05/EVA-10 |
| Q-10 | Kepanjangan "SEMANGAT" | Identitas produk |
| Q-11 | Tahapan final | `docs/03_ROADMAP.md` masih USULAN |
| NFR-12 | Retensi arsip daerah | Masa simpan audit (usulan ≥ 5 tahun) |
