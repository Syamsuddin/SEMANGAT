# 01 — PRD

Pemilik daftar fitur & user story. **Katalog lengkap 102 kebutuhan fungsional (ID `FON-01`…`EVA-10`, prioritas WAJIB/SEBAIKNYA/DAPAT) dan aturan bisnis per modul = `docs/_sumber/KEBUTUHAN.md`** (ekstraksi Bab 5 rancang bangun v0.1; PDF asli di `docs/_sumber/`). ID kebutuhan itu dipakai apa adanya di kode, tes, dan commit (mis. `test('IDP-10 ...')`). Dokumen ini hanya memuat ringkasan per modul + user story slice MVP.

## Modul (13, empat lapis)

| Modul | Awalan ID | Lapis | Inti fungsi | Prioritas rilis | Fase |
|---|---|---|---|---|---|
| M01 Fondasi | FON | A | SSO/login NIP, RBAC 9 peran, peran turunan, scope data, audit, 2FA, notifikasi, delegasi, verifikasi QR | MVP | F1 |
| M02 Integrasi | INT | A | Sinkron pegawai/jabatan, baca SKP, impor berkas, talenta, ekspor JP, panel kesehatan | MVP | F1 |
| M03 Profil kompetensi | KOM | A | SKJ, kamus, hasil uji, mesin kesenjangan, label keyakinan, asesmen mandiri, agregasi | MVP | F2 |
| M06 IDP | IDP | B | Draf otomatis, 2–3 kompetensi, tautan SKP, persetujuan (massal), Rencana Pembelajaran PDF, check-in, penutupan | MVP | F3 |
| M12 JP & riwayat | JPR | D | Rekap 20 JP, unggah & verifikasi sertifikat luar, riwayat, Laporan Hasil Pembelajaran | MVP | F4 |
| M07 Learning path | LPA | B | Jalur per jabatan/talenta, tag kompetensi wajib, rekomendasi ke IDP | Nanti | F5 |
| M08 Katalog 10:20:70 | KAT | C | Katalog semua bentuk bangkom, porsi 10/20/70, pencatatan porsi 70, pendaftaran, konversi JP | Nanti | F5 |
| M09 LMS | LMS | C | Kelas daring, e-learning, pre/post test, presensi QR, sertifikat ber-QR | Nanti | F5 |
| M13 Evaluasi | EVA | D | Kirkpatrick L1–L4, pengingat L3 T+3 bln, evaluasi sistem, dashboard berjenjang | Nanti | F6 |
| M04 Tata kelola | TKL | B | Registri SK, keanggotaan, forum 3 level, bahan otomatis, notulen/BA, pelacak keputusan, ringkasan 1 halaman | Nanti | F7 |
| M05 HCDP | HCD | B | Arah dari RPJMD, kesenjangan OPD, learning/non-learning, formulir KepLAN 604, konsolidasi, pengesahan | Nanti | F7 |
| M10 Komunitas & coaching | KCO | C | Komunitas praktik, pasangan coaching/mentoring, JP sesi, panen ke KMS | Nanti | F8 |
| M11 KMS | KMS | C | Repositori eksplisit, tacit, direktori pakar, kurasi, pencarian teks penuh | Nanti | F8 |

"MVP" = F1–F4 (slice poros IDP). Kebutuhan berprioritas **DAPAT** dan fitur AI (IDP-09, KMS-06) tidak dikerjakan tanpa instruksi (`docs/02_SCOPE.md`).

## User story slice MVP

| ID | Story |
|---|---|
| FON-01/02/04 | Sebagai ASN, saya masuk dengan NIP dan hanya melihat data dalam cakupan peran saya, agar data pegawai lain terlindungi. |
| FON-03 | Sebagai atasan, saya otomatis mendapat akses tim dari data jabatan, agar tidak perlu meminta hak ke admin. |
| FON-05 | Sebagai auditor, saya melihat jejak siapa membuka hasil asesmen siapa, agar kepatuhan PDP bisa dibuktikan. |
| INT-01/03/08 | Sebagai pengelola, saya menyinkronkan pegawai dari server pegawai dan mengimpor berkas SKP terkendali, lalu melihat kesehatan sinkron, agar data sumber dipakai tanpa salinan yang bersaing. |
| KOM-03/04/05 | Sebagai ASN, saya melihat kesenjangan SKJ vs level aktual dengan label keyakinannya, agar saya tahu apa yang perlu dikembangkan dan seberapa pasti datanya. |
| KOM-06 | Sebagai ASN tanpa hasil uji, saya mengisi asesmen mandiri yang divalidasi atasan, agar kesenjangan saya berlabel "sementara", bukan kosong. |
| IDP-01/02/03 | Sebagai ASN, saya menerima draf IDP otomatis lalu memilih 2–3 kompetensi prioritas yang tertaut ke hasil kerja SKP, agar IDP tidak menjadi formalitas. |
| IDP-04 | Sebagai atasan, saya menyetujui IDP tim secara massal dengan catatan, agar persetujuan selesai < 3 menit. |
| IDP-05 | Sebagai ASN, saya menerbitkan Rencana Pembelajaran (PDF ber-QR) + teks ekspektasi khusus "Kompeten" siap salin, agar bisa saya masukkan ke e-Kinerja. |
| IDP-06/07 | Sebagai ASN dan atasan, kami melakukan check-in dan menutup IDP dengan refleksi dan penilaian penerapan. |
| JPR-01/02/04 | Sebagai ASN, saya melihat saldo JP tahun berjalan dan mengunggah sertifikat bangkom luar untuk diverifikasi. |
| JPR-05 | Sebagai ASN, saya menerbitkan Laporan Hasil Pembelajaran di akhir periode, agar menjadi bukti dukung "Kompeten" di e-Kinerja. |

## Kebutuhan nonfungsional (ringkas; rinci NFR-01…13 di Bab 9 rancang bangun)
- P95 halaman & formulir ≤ 2 dtk; laporan/bahan forum/PDF dibuat di queue + notifikasi (NFR-01/02).
- Ketersediaan 99,5% jam kerja; backup DB harian + berkas mingguan, RPO 24 jam, RTO 8 jam (NFR-03/04).
- Fungsi inti pegawai tuntas di ponsel 360 px; formulir atasan ≤ 3 menit (NFR-05/06).
- WCAG 2.2 AA antarmuka pegawai (NFR-07); OpenAPI untuk semua API (NFR-09); Bahasa Indonesia baku (NFR-13).
