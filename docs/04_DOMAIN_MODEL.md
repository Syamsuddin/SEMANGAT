# 04 — Domain Model

Pemilik bahasa domain. Istilah UI = kolom "Istilah"; identifier kode = kolom "Kode". Skema fisik → `docs/07_DATA_MODEL.md`.

## Glosarium

| Istilah | Kode | Makna |
|---|---|---|
| ASN / Pegawai | `Employee` | PNS atau PPPK Pemkab HSS; diidentifikasi NIP (18 digit) |
| Atasan langsung | `supervisor` | Pejabat penilai kinerja; **diturunkan** dari data jabatan, tidak ditetapkan manual |
| OPD | `OrgUnit` (level opd) | Perangkat daerah; unit organisasi punya hierarki (OPD → bidang → subbidang) |
| Bangkom | — | Pengembangan kompetensi |
| JP | `jp` | Jam pelajaran; satuan pencatatan bangkom. Hak minimal **20 JP/pegawai/tahun** |
| 10:20:70 | `portion` ∈ {10,20,70} | 10 formal (kelas), 20 sosial (coaching/mentoring/komunitas), 70 di tempat kerja (penugasan/proyek) |
| Klasikal / nonklasikal | `mode` ∈ {classical, non_classical} | Jenis penyelenggaraan bangkom |
| SKJ | `JobStandard` | Standar Kompetensi Jabatan: level dipersyaratkan per kompetensi per jabatan (PermenPANRB 38/2017) |
| Kompetensi | `Competency` | Jenis: `managerial`, `sociocultural`, `technical`; level 1–5 |
| Hasil uji | `Assessment` | Level aktual dari uji resmi / asesmen mandiri; punya metode, lembaga, tanggal, masa berlaku |
| Kesenjangan | `Gap` | `required_level − actual_level` > 0 per pegawai per kompetensi |
| Label keyakinan | `confidence` | `verified` (terverifikasi: uji resmi) · `provisional` (sementara: asesmen mandiri divalidasi atasan) · `indicative` (indikatif: disimpulkan dari SKP/dialog kinerja) |
| SKP | `SkpSummary` | Sasaran Kinerja Pegawai; salinan **baca saja** dari e-Kinerja |
| RHK | `SkpWorkResult` | Rencana hasil kerja dalam SKP |
| Ekspektasi khusus pimpinan | `special_expectation` | Harapan pejabat penilai di SKP; jalur masuk pembelajaran ke SKP via perilaku "Kompeten" |
| Kotak talenta | `TalentBox` | Posisi 1–9 pemetaan kinerja×potensi (dibaca dari sistem talenta) |
| IDP | `Idp` | Individual Development Plan: rencana pengembangan individu per periode SKP, disusun bersama atasan |
| Butir IDP | `IdpItem` | Satu kompetensi prioritas (2–3 per IDP) dengan level awal/target dan tautan RHK |
| Check-in | `IdpCheckin` | Tinjauan berkala IDP yang menumpang jadwal dialog kinerja |
| Rencana Pembelajaran | `LearningPlanDocument` | PDF ber-QR awal periode dari IDP disetujui |
| Laporan Hasil Pembelajaran | `LearningReportDocument` | PDF ber-QR akhir periode: target/realisasi IDP, JP, bukti, refleksi, catatan atasan |
| HCDP | `Hcdp` | Human Capital Development Plan tingkat OPD / kabupaten (KepLAN 604/2025) |
| Learning / non-learning problem | `problem_type` | Kesenjangan yang diselesaikan dengan belajar vs rekomendasi ke pihak lain |
| Learning path | `LearningPath` | Jalur program bertag kompetensi per jabatan/rumpun/kelompok talenta |
| Program / Kelas | `Program` / `CourseClass` | Bentuk bangkom di katalog / satu penyelenggaraan berjadwal |
| Forum pembelajaran | `Forum` (`level` ∈ strategic, operational, technical) | **Mekanisme tata kelola**, bukan ruang diskusi |
| Dewan Pengarah / CLO / Tim Pelaksana | — | Unsur struktur Corpu dari SK (KepLAN 306/2024) |
| Evaluasi level 1–4 | `level` 1–4 | Kirkpatrick: reaksi, pembelajaran, perilaku, hasil |
| Sumber | `source`, `synced_at` | Asal & waktu tarik setiap rekaman hasil integrasi |

## Entitas inti & relasi konseptual

```
OrgUnit 1─N Position 1─N Employee ──(supervisor_id)──> Employee
Position N─1 JobStandard 1─N JobStandardItem N─1 Competency
Employee 1─N Assessment N─1 Competency          → Gap (Employee × Competency, confidence)
Employee 1─N SkpSummary 1─N SkpWorkResult
Employee 1─N Idp 1─N(2..3) IdpItem ─> Competency, ─> SkpWorkResult
                    IdpItem 1─N IdpActivity (portion 10/20/70) ─> Program?
Idp 1─N IdpCheckin ; Idp ─> LearningPlanDocument, LearningReportDocument
Employee 1─N JpRecord <─ (Enrollment | CoachingSession | WorkplaceLearning | Certificate)
Hcdp(opd|regency) 1─N HcdpNeed <─ agregasi Gap + IdpItem disetujui
Program 1─N CourseClass 1─N Enrollment ; Program N─N Competency (tag)
Forum 1─N Meeting 1─N Decision ; GovDecree 1─N GovMember
Program × Employee → EvalResponse (level 1–4)
```
Dua arus bertemu di perencanaan: HCDP (atas-bawah) ⇄ IDP (bawah-atas) saling mengoreksi (`docs/06_BUSINESS_PROCESS.md` §5).
