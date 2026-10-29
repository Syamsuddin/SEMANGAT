# 00 — Executive Summary

Sumber rancangan: *SEMANGAT CORPU Rancang Bangun v0.1* (29 Okt 2026, Syamsuddin, Bidang PSDM BKPSDM HSS). Bila blueprint ini bertentangan dengan PDF itu untuk **niat/aturan bisnis**, tandai selisihnya dan tanya — jangan memilih sendiri.

## Masalah
Pengembangan kompetensi ASN Pemkab Hulu Sungai Selatan masih: disusun dari kalender, bukan kesenjangan; hanya kelas yang tercatat (porsi 20/70 hilang); data pegawai, SKP, SKJ, asesmen, dan riwayat bangkom tercerai; dampak tidak terukur; tata kelola Corpu hanya di atas kertas.

## Solusi
SEMANGAT CORPU adalah aplikasi web yang dirancang ulang dari nol (menggantikan CORPU/LMS/KMS lama) sebagai **sistem operasi pembelajaran**, bukan sekadar LMS. Aplikasi menjalankan seluruh siklus Peraturan LAN 6/2023 + KepLAN 306/2024: arah daerah → profil & kesenjangan kompetensi → HCDP (atas-bawah) dan IDP (bawah-atas) yang saling mengoreksi → pembelajaran 10:20:70 → JP & pengetahuan → evaluasi dampak Kirkpatrick → kembali ke perencanaan. 13 modul dalam 4 lapis (detail: `docs/01_PRD.md`).

## Kesepakatan yang tidak bisa ditawar
- **IDP adalah modul poros.**
- **SKP dibaca, tidak ditulis**: empat titik sentuh, arus satu arah dari e-Kinerja ke Corpu.
- **Pembelajaran tidak dijadikan RHK**: masuk SKP lewat perilaku "Kompeten"; aplikasi menerbitkan Rencana Pembelajaran (awal) dan Laporan Hasil Pembelajaran (akhir) sebagai bukti dukung.
- **Data jujur**: setiap data kompetensi berlabel keyakinan (terverifikasi/sementara/indikatif).
- **Tata kelola mewajibkan, aplikasi melayani.**

## Nilai & metrik sukses
- Utama `[ASUMSI]`: % ASN dengan IDP disetujui **dan** ditutup dengan Laporan Hasil Pembelajaran per periode SKP.
- Pendamping: % ASN mencapai 20 JP/tahun (PP 11/2017 jo 17/2020).

## Pengguna sasaran
Seluruh ASN (PNS & PPPK) Pemkab HSS, atasan langsung, koordinator OPD, fasilitator, pengelola Corpu (Bidang PSDM), CLO, Dewan Pengarah, admin teknis, auditor (`docs/05_USER_ROLE.md`).

## Status
Greenfield; blueprint diturunkan dari rancang bangun v0.1 (draf untuk tinjauan). Fase pembangunan berstatus USULAN (`docs/03_ROADMAP.md`); keputusan terbuka Q-01…Q-11 dicatat di `docs/02_SCOPE.md`.
