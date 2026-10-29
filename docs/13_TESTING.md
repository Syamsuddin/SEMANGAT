# 13 — Testing

Pemilik strategi tes (Bab 15.1 rancang bangun). Perintah: `docs/11_COMMANDS.md`. Pest 3, DB tes MySQL `semangat_test` (RefreshDatabase) — **bukan SQLite**, agar perilaku enum/JSON/FULLTEXT sama dengan produksi.

## Jenis & cakupan
| Jenis | Lokasi | Wajib untuk | Target |
|---|---|---|---|
| Unit aturan bisnis | `tests/Unit/<Modul>` | `GapCalculator` (prioritas keyakinan, kedaluwarsa), `JpConverter`, saldo 20 JP, batas 2–3 butir IDP, transisi status IDP | 100% cabang Service inti |
| Feature | `tests/Feature/<Modul>` | tiap kebutuhan WAJIB slice aktif (Livewire::test / HTTP) | ≥1 tes per ID kebutuhan, nama memuat ID |
| Scope (bocor data) | `tests/Scope` | setiap halaman/aksi ber-data diuji untuk **semua 9 peran**: yang di luar cakupan → 403/404, daftar tidak memuat baris orang lain | tiap rute baru |
| Audit | dalam Feature | tiap baca/tulis data Tinggi menghasilkan baris `sys_audit_logs` | tiap model 🔒 |
| Arsitektur | `tests/Arch` | `arch()`: Controller/Livewire tidak memakai `DB::`; hanya `Integrasi\Adapters` memakai `Http::`; tidak ada kelas yang mengimplementasi tulis ke e-Kinerja; tidak ada `dd/dump/ray` | selalu |
| Kontrak adaptor | `tests/Contract` | tiap adaptor memetakan fixture sumber (`tests/Fixtures/<sumber>/`) ke DTO; impor berkas rusak ditolak utuh | tiap adaptor |
| Dokumen | Feature | PDF terbit, `out_issued_documents` tercatat, `/verifikasi/{uuid}` menampilkan status | tiap jenis dokumen |
| E2E siklus | `tests/Feature/CycleTest.php` | draf IDP → setuju → Rencana → JP → check-in → tutup → Laporan | sejak F4 |
| Kinerja | k6/JMeter di staging `[ASUMSI]` | NFR-01 P95 ≤ 2 dtk | pra-rilis |
| Aksesibilitas | axe-core (Playwright) pada halaman pegawai | WCAG 2.2 AA | pra-rilis |
| Kegunaan & UAT | manual, perwakilan tiap peran, ponsel 360 px | NFR-05/06 | akhir tiap fase |

## Tes yang tidak boleh hilang (sentinel)
- `INT-02`: tidak ada operasi tulis ke e-Kinerja (arch test + `Http::preventStrayRequests()` + assert tidak ada request non-GET ke host e-Kinerja).
- `KOM-05`: tampilan gap indikatif memakai komponen badge `indicative`, bukan `verified`.
- `IDP-02`: butir ke-4 ditolak; ajukan dengan <2 butir ditolak.
- `JPR-02`: sertifikat `pending` tidak menambah saldo.
- `EVA` aturan: data evaluasi tidak muncul di ekspor/halaman yang berhubungan dengan kinerja.

## Aturan
- Factory per model di modulnya; tanpa data produksi.
- `Http::fake()` untuk semua adaptor; `Storage::fake('s3')`, `Queue::fake()`/`Event::fake()` di unit, nyata di E2E.
- Waktu dibekukan (`$this->travelTo()`) untuk pengingat T+3 bulan & masa berlaku.
