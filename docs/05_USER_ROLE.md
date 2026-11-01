# 05 — User Role

Pemilik peran & matriks akses (dari Bab 10.1 rancang bangun, berstatus USULAN). Penegakan teknis → `docs/21_SECURITY_RULES.md`.

## Peran

| Kode peran | Nama | Diperoleh dari | Cakupan data | 2FA |
|---|---|---|---|---|
| `asn` | ASN (pegawai) | Otomatis: setiap pegawai aktif | sendiri (S) | — |
| `atasan` | Atasan langsung | **Turunan** dari `hr_employees.supervisor_id` (FON-03) | tim (T) = bawahan langsung | — |
| `koordinator_opd` | Koordinator OPD | Penugasan pengelola per OPD | OPD (O) | — |
| `fasilitator` | Fasilitator/pakar/coach/mentor | Penugasan per kelas/sesi | kelas/sesi yang ditugaskan | — |
| `pengelola` | Pengelola Corpu (Bidang PSDM) | Turunan dari registri SK (M04) atau penugasan admin | kabupaten, kelola (K) | wajib |
| `clo` | CLO / koordinator pembelajaran | Turunan registri SK | kabupaten, agregat (A) | wajib |
| `dewan_pengarah` | Dewan Pengarah | Turunan registri SK | agregat/ringkasan (R) | — |
| `admin_teknis` | Administrator teknis | Penugasan manual tercatat | konfigurasi sistem, tanpa isi data sensitif | wajib |
| `auditor` | Auditor | Penugasan manual tercatat | baca seluruh (B) termasuk audit log | wajib |

Satu pengguna dapat memegang beberapa peran; cakupan = gabungan. Hak tidak pernah diberikan manual per orang bila bisa diturunkan dari jabatan/SK. Koreksi manual turunan wajib beralasan + kedaluwarsa otomatis (`gov_role_overrides`, `docs/07_DATA_MODEL.md`). Delegasi persetujuan sementara (FON-08) memberi hak `atasan` atas tim pendelegasi selama masa berlaku.

## Matriks akses

Keterangan: **K** kelola · **S** data sendiri · **T** tim · **O** OPD · **A** agregat kabupaten (tanpa identitas individu bila n<5) · **B** baca seluruh · **R** ringkasan · **—** tidak ada akses.

| Modul | asn | atasan | koord. OPD | fasilitator | pengelola | clo | dewan | auditor |
|---|---|---|---|---|---|---|---|---|
| M01 Profil akun & notifikasi | S | S | S | S | K | S | S | B |
| M02 Integrasi & panel kesehatan | — | — | — | — | K | R | — | B |
| M03 Profil kompetensi (ringkas) | S | T | O | — | B | A | A | B |
| M03 Laporan asesmen rinci / psikogram | S | — | — | — | K* | — | — | B* |
| M04 Tata kelola | — | — | R | — | K | K | R | B |
| M05 HCDP | — | — | K (OPD) | — | K | K | R | B |
| M06 IDP | K (S) | K (T) | O | — | B | A | — | B |
| M07–M09 Strategi, katalog, LMS | S | T | O | K (kelas) | K | K | — | B |
| M10 Catatan coaching | S | — | — | K (sesi) | — | — | — | — |
| M11 KMS | B | B | B | K (kurasi) | K | B | B | B |
| M12 JP & riwayat | S | T | O | — | K | A | A | B |
| M13 Evaluasi & dashboard | S | T | O | R (kelas) | K | A | A | B |

\* Hanya petugas yang ditunjuk, dengan **alasan pembukaan tercatat** di audit log.

## Kapabilitas kunci per peran (non-obvious)
- `asn`: tidak dapat menyetujui IDP sendiri; selalu dapat melihat seluruh datanya termasuk label keyakinan dan **siapa yang membuka laporan asesmennya** (transparansi PDP).
- `atasan`: melihat **ringkasan** kesenjangan tim, bukan psikogram; menyetujui IDP & validasi asesmen mandiri bawahan langsung saja.
- `fasilitator`: tidak melihat profil kompetensi peserta; catatan coaching hanya coach & coachee.
- `admin_teknis`: tidak membuka isi data berklasifikasi Tinggi.
- `auditor`: baca-saja; tidak ada aksi tulis di modul mana pun.
- Tidak ada peran yang dapat mengubah data SKP salinan (sumber menang).
