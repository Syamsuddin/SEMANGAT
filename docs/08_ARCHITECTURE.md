# 08 — Architecture

Pemilik pola arsitektur. Teknologi & versi: `docs/09_STACK.md`. Struktur folder: `docs/12_PROJECT_STRUCTURE.md`.

## Gaya: monolit modular
Satu basis kode Laravel, satu DB, **13 modul dengan batas tegas** (`app/Modules/<Modul>`). Dipilih karena tim kecil/pengembang tunggal (risiko R-12) dan pemasangan di server pemda; batas modul memungkinkan memindahkan domain kompetensi ke lapisan data bersama kelak (Bab 11.1).

```
Browser (Blade + Livewire 3 + Alpine, mobile-first)
   │ HTTPS
Nginx ─► PHP-FPM: Laravel 12
   ├─ HTTP/Livewire layer (tipis)  → validasi FormRequest/rules, authorize Policy, panggil Action
   ├─ Domain layer per modul       → Actions (satu kasus pakai), Services (kalkulasi), Models, Enums, Events
   ├─ Integration layer (M02)      → Contracts + Adapters per sumber
   ├─ Document layer               → Blade→PDF (dompdf) + QR, disimpan MinIO, didaftar out_issued_documents
   └─ Jobs/Scheduler               → Redis queue: sinkron, kesenjangan, PDF, laporan, notifikasi, pengingat
MySQL 8 │ Redis 7 (queue, cache, session) │ MinIO (S3: berkas, sertifikat, PDF, video)
```

## Tanggung jawab lapisan
| Lapisan | Boleh | Tidak boleh |
|---|---|---|
| Livewire component / Controller | ambil input, `authorize()`, panggil 1 Action, kembalikan view | query lintas modul, aturan bisnis, hitung JP/gap |
| Action (`app/Modules/X/Actions`) | satu kasus pakai transaksional (mis. `SubmitIdp`), memicu Event | memanggil adaptor eksternal langsung (lewat Contract) |
| Service | kalkulasi murni/teruji (mis. `GapCalculator`, `JpConverter`) | I/O ke HTTP |
| Model | relasi, cast, scope query (`scopeVisibleTo($user)`) | logika alur |
| Adapter | terjemahkan format sumber ⇄ DTO internal | menulis ke e-Kinerja (tidak ada metode tulis) |

## Komunikasi antar-modul
- Modul membaca modul lain **hanya** lewat kelas publiknya (`Contracts/` atau Service bernama jelas) atau Event; tidak menyentuh tabel modul lain secara langsung untuk menulis.
- Arus data utama via Event Laravel (queued listener): `AssessmentRecorded` → M03 `RecalculateGaps`; `IdpApproved` → M05 agregasi; `ClassCompleted` / `CoachingSessionLogged` / `WorkplaceLearningApproved` → M12 `RecordJp`; `ProgramCompleted` → M13 jadwal L3 T+3 bln.
- Pemeriksaan batas modul otomatis: tes arsitektur Pest (`arch()`) di `tests/Arch` (`docs/13_TESTING.md`).

## Integrasi eksternal
| Sistem | Arah | Contract | Adaptor awal | Status |
|---|---|---|---|---|
| Server pegawai lokal (SIASN) | masuk | `EmployeeSource` | `SimpegApiAdapter` (REST) + `EmployeeFileImportAdapter` (XLSX/CSV) | PERLU KONFIRMASI (Q-06) |
| e-Kinerja | masuk, **baca saja** | `SkpSource` (hanya metode `fetch*`) | `SkpFileImportAdapter`; `EkinerjaApiAdapter` saat Q-02 terjawab | PERLU KONFIRMASI |
| Sistem talenta | masuk | `TalentSource` | `TalentFileImportAdapter` | PERLU KONFIRMASI |
| Lapisan kompetensi bersama | dua arah | `CompetencySource` | lokal (tabel `cmp_*`) | USULAN |
| Platform LAN / kepegawaian | keluar | `JpExporter` | ekspor berkas sesuai format | TERBUKA (Q-09) |
| SSO pemda | masuk | `IdentityProvider` | login lokal NIP | ASUMSI |
Prinsip: adaptor dapat diganti lewat binding di `config/semangat.php` tanpa mengubah modul lain; sinkron idempoten; sumber menang; uji kontrak setiap adaptor.

## API
Belum ada konsumen eksternal di MVP. API yang kelak dibuka (`/api/v1`, Sanctum token) wajib didokumentasikan OpenAPI (NFR-09) di `docs/api/openapi.yaml`.

## Keputusan arsitektural kunci
| # | Keputusan | Alasan |
|---|---|---|
| A1 | Monolit modular, bukan microservice | tim kecil, server pemda (Bab 11.1) |
| A2 | Livewire, bukan SPA | satu deployable, tanpa API internal ganda |
| A3 | Kontrak SKP tanpa metode tulis | arus satu arah dijamin oleh tipe, bukan disiplin (INT-02) |
| A4 | Kesenjangan dimaterialisasi di `cmp_gaps` | dashboard & agregasi OPD cepat; dihitung ulang via event |
| A5 | Semua PDF lewat satu `DocumentIssuer` | QR, hash, dan registri verifikasi seragam (FON-09) |
| A6 | Pekerjaan berat di queue | NFR-01/02 |
