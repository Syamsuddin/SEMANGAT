# 07 — Data Model

**Sumber kebenaran skema.** Dokumen lain merujuk, tidak menyalin. MySQL 8.0 (`utf8mb4_unicode_ci`, InnoDB), kompatibel MariaDB 10.11. Begitu migrasi ada, **migrasi menang** atas dokumen ini: selisih → berhenti, lapor, perbarui dokumen ini.

## Konvensi umum
- Prefix tabel = domain (Bab 7.1 rancang bangun): `hr_` kepegawaian · `cmp_` kompetensi · `gov_` tata kelola · `pln_` perencanaan · `lrn_` pembelajaran · `out_` keluaran & evaluasi · `sys_` fondasi. Tabel bawaan Laravel/paket (`users`, `sessions`, `jobs`, `failed_jobs`, `notifications`, `roles`, `permissions`, `model_has_roles`, …) tanpa prefix.
- Setiap tabel: `id bigint unsigned PK auto`, `created_at`, `updated_at`. Tabel milik Corpu yang punya riwayat bisnis memakai `deleted_at` (soft delete); **pegawai tidak pernah dihapus**, hanya `status=inactive`.
- **Kolom integrasi** (wajib di setiap tabel hasil tarik sumber, ditandai ⇅): `source varchar(40) NOT NULL` (mis. `simpeg_api`, `skp_file`, `ekinerja_api`), `external_id varchar(64) NOT NULL`, `synced_at datetime NOT NULL`; UNIQUE(`source`,`external_id`). Tabel ⇅ **read-only** bagi aplikasi kecuali lewat adaptor M02.
- **Kolom keyakinan**: `confidence enum('verified','provisional','indicative') NOT NULL`.
- **Kolom terenkripsi** (🔒): disimpan `text`, di-cast `encrypted` Laravel (`APP_KEY`); tidak dapat diindeks/dicari. Setiap pembacaan model berkolom 🔒 dicatat di `sys_audit_logs` (`docs/21_SECURITY_RULES.md`).
- FK default `ON DELETE RESTRICT`; enum disimpan sebagai `varchar` + PHP backed enum di `app/Modules/*/Enums` bila nilai mungkin bertambah.

## F1 — Fondasi & kepegawaian

### users
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| id | bigint unsigned | PK | |
| employee_id | bigint unsigned | FK→hr_employees.id, UNIQUE, NULL | NULL hanya untuk akun teknis non-pegawai |
| username | varchar(32) | UNIQUE, NOT NULL | = NIP untuk pegawai |
| password | varchar(255) | NOT NULL | bcrypt (hash bawaan Laravel) |
| two_factor_secret / two_factor_recovery_codes | text | NULL | Fortify, 🔒 |
| two_factor_confirmed_at | timestamp | NULL | |
| is_active | boolean | NOT NULL default 1 | diturunkan dari `hr_employees.status` |
| last_login_at | timestamp | NULL | |
| notification_prefs | json | NULL | {"mail":true,"inapp":true,"wa":false} (FON-07) |
| remember_token | varchar(100) | NULL | bawaan Laravel ("Ingat saya") |

### hr_org_units ⇅
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| parent_id | bigint unsigned | FK→hr_org_units.id, NULL | hierarki |
| code | varchar(40) | NOT NULL | |
| name | varchar(200) | NOT NULL | |
| level | varchar(20) | NOT NULL | `regency`,`opd`,`division`,`section` |
| opd_id | bigint unsigned | FK→hr_org_units.id, NULL | OPD induk (denormalisasi untuk scope O) |
| + source, external_id, synced_at | | | |

### hr_positions ⇅ (Jabatan)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| code | varchar(40) | NOT NULL | |
| name | varchar(200) | NOT NULL | |
| type | varchar(20) | NOT NULL | `structural`,`functional`,`executor` |
| grade_class | tinyint unsigned | NULL | kelas jabatan |
| family | varchar(100) | NULL | rumpun jabatan |
| tier | varchar(20) | NOT NULL | jenjang untuk template "Kompeten": `pelaksana`,`fungsional`,`pengawas`,`administrator_jpt` |
| job_standard_id | bigint unsigned | FK→cmp_job_standards.id, NULL | SKJ acuan |
| + source, external_id, synced_at | | | |

### hr_employees ⇅
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| nip | char(18) | UNIQUE, NOT NULL | Pribadi |
| name | varchar(150) | NOT NULL | |
| employment_type | varchar(10) | NOT NULL | `pns`,`pppk` |
| status | varchar(12) | NOT NULL | `active`,`inactive` (pensiun/berhenti/pindah) |
| org_unit_id | bigint unsigned | FK→hr_org_units.id | |
| opd_id | bigint unsigned | FK→hr_org_units.id | denormalisasi scope O |
| position_id | bigint unsigned | FK→hr_positions.id, NULL | jabatan aktif |
| supervisor_id | bigint unsigned | FK→hr_employees.id, NULL | **turunan**; sumber peran `atasan` |
| email / phone | varchar(150) / varchar(20) | NULL | notifikasi |
| + source, external_id, synced_at | | | |
Indeks: (opd_id, status), (supervisor_id).

### hr_position_histories ⇅
employee_id FK, position_id FK, org_unit_id FK, started_on date, ended_on date NULL, + kolom integrasi. **Belum dimigrasi di F1** — sumber belum menyediakan riwayat (Q-06); dibuat saat format sumber pasti.

Catatan migrasi F1: `hr_positions.job_standard_id` sudah ada tetapi FK→`cmp_job_standards` ditambahkan pada F2. Pegawai di-upsert berkunci `nip` (UNIQUE); unit & jabatan berkunci (`source`, `external_id` = kode).

### hr_talent_boxes ⇅ (INT-04, F5+)
employee_id FK, period_year smallint, box tinyint (1–9), + kolom integrasi. UNIQUE(employee_id, period_year).

### hr_skp_summaries ⇅ (SKPRingkas — BACA SAJA)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| employee_id | bigint unsigned | FK→hr_employees.id | |
| period_year | smallint | NOT NULL | UNIQUE(employee_id, period_year, source) |
| dialogue_notes | text | NULL | catatan dialog kinerja (Pribadi) |
| realization_notes | text | NULL | realisasi akhir periode (titik sentuh 4) |
| + source, external_id, synced_at | | | |

### hr_skp_work_results ⇅ (RHK)
skp_summary_id FK, code varchar(40), description text, special_expectation text NULL, realization text NULL, + kolom integrasi. Tidak ada kolom predikat/nilai — **dilarang ditambahkan** (`docs/02_SCOPE.md`).

### sys_audit_logs (append-only)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| user_id | bigint unsigned | NULL | pelaku (NULL = sistem) |
| action | varchar(40) | NOT NULL | `read_sensitive`,`create`,`update`,`delete`,`login`,`export`,`role_override`, … |
| auditable_type / auditable_id | varchar(120) / bigint | NOT NULL | objek |
| reason | varchar(255) | NULL | wajib untuk pembukaan laporan asesmen rinci |
| changes | json | NULL | before/after untuk tulis (tanpa nilai 🔒) |
| ip_address | varchar(45) | NOT NULL | |
| created_at | datetime(3) | NOT NULL | tanpa `updated_at` |
Tak dapat diubah: user DB aplikasi hanya punya hak INSERT/SELECT pada tabel ini (`docs/21_SECURITY_RULES.md`). Indeks: (auditable_type, auditable_id), (user_id, created_at).

### sys_delegations (FON-08)
delegator_employee_id FK, delegate_employee_id FK, capability varchar(40) (`approve_idp`,`validate_assessment`,`eval_l3`), starts_at, ends_at datetime NOT NULL, reason varchar(255).

### gov_role_overrides (koreksi peran turunan)
user_id FK, role varchar(40), scope_type varchar(20) NULL, scope_id bigint NULL, reason varchar(255) NOT NULL, granted_by FK→users, expires_at datetime NOT NULL.

### sys_role_assignments (penugasan peran manual tercatat — keputusan pemilik produk 2026-10-29)
user_id FK→users, role varchar(40) (hanya peran non-turunan: `koordinator_opd`,`fasilitator`,`pengelola`,`clo`,`dewan_pengarah`,`admin_teknis`,`auditor`), scope_type varchar(20) NULL (`opd` untuk koordinator), scope_id bigint NULL (→hr_org_units.id level `opd`), reason varchar(255) NOT NULL, assigned_by FK→users NULL, starts_at datetime, ends_at datetime NULL, revoked_at datetime NULL. Indeks (user_id, role). Peran spatie = materialisasi `SyncUserRoles` dari: pegawai aktif → `asn`, bawahan langsung aktif → `atasan`, penugasan berlaku di tabel ini. Setiap penugasan/pencabutan → `sys_audit_logs` (`role_assign`/`role_revoke`). Saat registri SK (M04) ada, `pengelola`/`clo`/`dewan_pengarah` diturunkan dari sana.

### int_sync_runs (INT-08)
adapter varchar(60), source_ref varchar(255) NULL (rujukan berkas/parameter agar run dapat diulang dari panel), started_at, finished_at NULL, status varchar(12) (`running`,`success`,`partial`,`failed`), rows_ok int, rows_failed int, error text NULL, triggered_by FK→users NULL. Indeks (adapter, started_at).

### int_sync_failures
sync_run_id FK, row_number int unsigned NULL (baris berkas, untuk laporan per baris), external_id varchar(64) NULL (NULL bila baris tanpa NIP), payload json, error text, retried_at NULL, resolved_at NULL.

### out_issued_documents (FON-09 — semua PDF terbitan)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| uuid | char(36) | UNIQUE | dipakai di URL QR `/verifikasi/{uuid}` |
| type | varchar(40) | NOT NULL | `learning_plan`,`learning_report`,`certificate`,`hcdp`,`forum_minutes`,`strategic_brief`,`corpu_report` |
| documentable_type / documentable_id | varchar(120) / bigint | NOT NULL | sumber |
| employee_id | bigint unsigned | FK, NULL | pemilik bila personal |
| file_path | varchar(255) | NOT NULL | key objek MinIO |
| sha256 | char(64) | NOT NULL | integritas |
| issued_at | datetime | NOT NULL | |
| revoked_at | datetime | NULL | dokumen dicabut tetap terverifikasi sebagai "dicabut" |
Halaman verifikasi publik hanya menampilkan: jenis, nama pemilik, tanggal terbit, status, hash — tanpa isi.

## F2 — Kompetensi

### cmp_competencies (kamus, KOM-02)
code varchar(40) UNIQUE, name varchar(200), type varchar(20) (`managerial`,`sociocultural`,`technical`), description text, level_descriptors json (level 1–5), steward varchar(150) NULL (instansi pembina), is_active boolean.

### cmp_job_standards / cmp_job_standard_items (SKJ, KOM-01)
`cmp_job_standards`: position_code varchar(40), name, effective_on date, + kolom integrasi NULLable (bisa input manual sampai lapisan data bersama siap).
`cmp_job_standard_items`: job_standard_id FK, competency_id FK, required_level tinyint (1–5). UNIQUE(job_standard_id, competency_id).

### cmp_assessments (HasilUji — Sensitivitas Tinggi)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| employee_id | bigint unsigned | FK→hr_employees.id | |
| competency_id | bigint unsigned | FK→cmp_competencies.id | |
| level | tinyint unsigned | NOT NULL, 1–5 | |
| method | varchar(30) | NOT NULL | `assessment_center`,`jf_competency_test`,`self_assessment`,`inferred_skp` |
| confidence | enum | NOT NULL | turunan dari method + validasi |
| institution | varchar(150) | NULL | lembaga penguji |
| assessed_on | date | NOT NULL | |
| valid_until | date | NULL | KOM-07 peringatan |
| validated_by | bigint unsigned | FK→hr_employees.id, NULL | atasan (self_assessment) |
| validated_at | datetime | NULL | |
| rejected_at | datetime | NULL | asesmen mandiri ditolak atasan (KOM-06; keputusan pemilik produk 2026-10-30) |
| rejection_note | varchar(255) | NULL | alasan penolakan, terlihat pegawai |
| report_file_path | text | NULL | 🔒 key berkas psikogram/laporan rinci |
| notes | text | NULL | 🔒 |
Aturan: `self_assessment` tanpa `validated_at` (menunggu) atau ber-`rejected_at` (ditolak) → tidak dipakai mesin kesenjangan; `validated_by` = atasan yang memutuskan (setuju/tolak). Indeks: (employee_id, competency_id, assessed_on).

### cmp_gaps (materialisasi, dihitung ulang oleh `RecalculateGaps`)
employee_id FK, competency_id FK, required_level tinyint, actual_level tinyint NULL, gap tinyint, confidence enum, assessment_id FK NULL, calculated_at datetime. UNIQUE(employee_id, competency_id). Sensitivitas Tinggi (akses via scope, bukan enkripsi — dibutuhkan untuk agregasi).

## F3 — IDP

### pln_idps
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| employee_id | bigint unsigned | FK→hr_employees.id | |
| supervisor_id | bigint unsigned | FK→hr_employees.id | dibekukan saat ajukan |
| period_year | smallint | NOT NULL | UNIQUE(employee_id, period_year) di antara yang belum `cancelled` |
| status | varchar(12) | NOT NULL | `draft`,`submitted`,`approved`,`active`,`closed`,`cancelled` |
| template | varchar(20) | NOT NULL default `standard` | `standard`,`acceleration`,`improvement` (IDP-08) |
| career_aspiration | text | NULL | IDP-03 |
| target_jp | smallint unsigned | NOT NULL default 20 | |
| supervisor_note | text | NULL | |
| submitted_at / approved_at / closed_at | datetime | NULL | |
| closing_reflection | text | NULL | IDP-07 (asn) |
| closing_assessment | text | NULL | IDP-07 (atasan: penerapan di pekerjaan) |
| unlinked_items_count | tinyint | NOT NULL default 0 | penanda IDP-10 |

### pln_idp_items (2–3 per IDP saat diajukan — ditegakkan di Action + tes)
idp_id FK, competency_id FK, gap_id FK NULL, start_level tinyint, target_level tinyint, skp_work_result_id FK→hr_skp_work_results NULL (NULL = tidak tertaut → ditandai), success_indicator text, mentor_employee_id FK NULL, position tinyint (1–3). UNIQUE(idp_id, competency_id).

### pln_idp_activities
idp_item_id FK, portion tinyint (10/20/70), title varchar(200), program_id FK→lrn_programs NULL, planned_jp smallint, planned_start date, planned_end date, status varchar(12) (`planned`,`done`,`dropped`).

### pln_idp_checkins (IDP-06)
idp_id FK, checkin_on date, quarter tinyint, employee_note text, supervisor_note text NULL, progress json ({item_id: pct}).

Catatan migrasi F3: UNIQUE "di antara yang belum `cancelled`" ditegakkan lewat kolom bangkitan `pln_idps.open_period_year` (STORED, NULL bila `cancelled`) + UNIQUE(employee_id, open_period_year). `supervisor_id` NULL selama `draft` (dibekukan saat ajukan). Tabel anak (`pln_idp_items`, `_activities`, `_checkins`) `ON DELETE CASCADE` ke induknya; `pln_idp_items.gap_id` `ON DELETE SET NULL` karena `cmp_gaps` dihapus saat kesenjangan tertutup. `pln_idp_activities.program_id` tanpa FK sampai `lrn_programs` ada (F5). `pln_idp_checkins` UNIQUE(idp_id, quarter). Tabel bawaan `notifications` (kanal `database`) dimigrasi di F3 untuk notifikasi IDP.

## F4 — JP & riwayat

### out_jp_records (CatatanJP)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| employee_id | bigint unsigned | FK | |
| year | smallint | NOT NULL | tahun anggaran pemenuhan 20 JP |
| sourceable_type / sourceable_id | varchar(120) / bigint | NULL | Enrollment, CoachingSession, WorkplaceLearning, Certificate |
| title | varchar(255) | NOT NULL | |
| portion | tinyint | NOT NULL | 10/20/70 |
| mode | varchar(15) | NOT NULL | `classical`,`non_classical` |
| jp | decimal(5,1) | NOT NULL, > 0 | hasil konversi (KAT-06) |
| activity_start / activity_end | date | NOT NULL | |
| status | varchar(10) | NOT NULL | `pending`,`verified`,`rejected` |
| verified_by / verified_at / reject_reason | | NULL | |
Aturan: saldo JP = SUM(jp) WHERE status='verified'. Indeks: (employee_id, year, status).

### out_certificates
employee_id FK, jp_record_id FK UNIQUE, issuer varchar(200), number varchar(100) NULL, file_path varchar(255), sha256 char(64), is_internal boolean (internal → punya `out_issued_documents`). UNIQUE(employee_id, sha256) — deteksi duplikat.

### pln_jp_conversion_rules (KAT-06, dikelola pengelola)
activity_type varchar(40), unit varchar(20) (`hour`,`day`,`session`,`activity`), jp_per_unit decimal(4,1), max_jp decimal(5,1) NULL, effective_from date. Nilai konkret ditetapkan pengelola di F5 (KAT-06). Sampai itu (F4) JP sertifikat luar diisi pegawai sesuai sertifikat lalu dikonfirmasi/dikoreksi verifikator (keputusan pemilik produk 2026-11-01).

Catatan migrasi F4: `out_jp_records.jp` ditegakkan juga oleh CHECK `jp > 0`; `verified_by` FK→`users` (NULL untuk catatan otomatis JPR-03); UNIQUE (sourceable_type, sourceable_id) menegakkan idempotensi pencatatan otomatis juga saat serentak (NULL untuk unggah manual boleh berulang); `year` = tahun `activity_end`. `out_certificates.jp_record_id` `ON DELETE CASCADE`. Keduanya tanpa `deleted_at`: catatan JP tidak dihapus, penolakan = status `rejected`. `pln_jp_conversion_rules` belum dimigrasi (KAT-06, F5).

## F5+ — tabel inti fase berikutnya (rincikan kolom saat fasenya dimulai, lewat rute "Ubah skema DB")
| Tabel | Kunci & relasi | Catatan |
|---|---|---|
| `lrn_programs` | type (KAT-01 jenis), portion, mode, jp, status (`draft`,`published`) | terbit hanya bila punya ≥1 tag kompetensi (LPA-02) |
| `lrn_program_competency` | program_id, competency_id, target_level | pivot tag |
| `pln_learning_paths` / `_steps` | position_family / talent_group; step→program | M07 |
| `lrn_course_classes` | program_id, schedule, quota, delivery (`online`,`self_paced`,`face_to_face`) | M09 |
| `lrn_enrollments` | class_id, employee_id, status (`waitlist`,`approved`,`completed`), approved_by | KAT-04 |
| `lrn_contents`, `lrn_progress`, `lrn_quizzes`, `lrn_question_bank`, `lrn_attempts`, `lrn_attendances` | per kelas | LMS-01…06 |
| `lrn_workplace_learnings` | employee_id, idp_activity_id, type (`assignment`,`project`,`special_expectation`), skp_work_result_id (rujuk bukti, tidak unggah ulang) | KAT-03 |
| `out_eval_instruments` / `_questions` | level 1–4, max 5 pertanyaan untuk L3 | EVA-01 |
| `out_eval_responses` | instrument_id, program_id, employee_id (subjek), respondent_id, level, answers json, due_at, submitted_at; UNIQUE(program_id, employee_id, level, respondent_id) | EVA-02…05 |
| `gov_decrees`, `gov_members` | nomor, tanggal, masa berlaku; member unsur & masa tugas | TKL-01/02 |
| `gov_forums`, `gov_meetings`, `gov_meeting_attendees`, `gov_decisions` | level, jadwal; decision: pic, due_on, status, escalated_from | TKL-04…09 |
| `gov_policy_documents` | versi, status (`draft`,`in_force`,`revoked`) | TKL-10 |
| `pln_directions`, `pln_hcdps`, `pln_hcdp_needs` | scope (`opd`,`regency`), year, status pengesahan; need: problem_type, priority, budget_ceiling | M05 |
| `lrn_communities`, `lrn_posts` | per rumpun/tema | KCO-01/05 |
| `lrn_coaching_pairs`, `lrn_coaching_sessions` | coach, coachee, target; session.notes 🔒, shared_summary | KCO-02/03; catatan rahasia |
| `lrn_knowledge_artifacts` | type (`sop`,`study`,`lesson_learned`,`handover`, …), status (`draft`,`curated`,`published`), FULLTEXT(title, body) | KMS-01…05 |
| `lrn_expert_directory` | employee_id, competency_id, verified_by | KMS-03 |

## Retensi
Audit log 5 tahun (NFR-12, keputusan pemilik produk 2026-11-01; dicocokkan dengan Jadwal Retensi Arsip daerah sebelum rilis). Riwayat bangkom pegawai nonaktif disimpan sesuai ketentuan arsip.
