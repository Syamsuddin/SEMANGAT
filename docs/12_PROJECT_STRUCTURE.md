# 12 — Project Structure

Pemilik struktur folder & penamaan.

## Pohon
```
app/
  Modules/
    Fondasi/            # M01 (auth, peran, audit, notifikasi, delegasi, verifikasi dokumen)
    Integrasi/          # M02 (Contracts/, Adapters/, Jobs/, panel kesehatan)
    Kompetensi/         # M03
    TataKelola/         # M04
    Hcdp/               # M05
    Idp/                # M06
    LearningPath/       # M07
    Katalog/            # M08
    Lms/                # M09
    Komunitas/          # M10
    Pengetahuan/        # M11
    JpRiwayat/          # M12
    Evaluasi/           # M13
      └─ (setiap modul)
         Actions/  Services/  Models/  Enums/  Events/  Listeners/  Jobs/  Policies/
         Livewire/  Http/Controllers/  Data/ (DTO)  Database/{Migrations,Factories,Seeders}/
         resources/views/  routes.php  <Modul>ServiceProvider.php
  Support/              # lintas modul: DocumentIssuer, AuditLogger, Scope/VisibleTo trait, Enums/Confidence
resources/views/
  components/           # komponen Blade UI bersama (docs/26_UI_CONVENTIONS.md)
  layouts/              # app.blade.php (shell + nav), guest.blade.php, pdf.blade.php
resources/css/app.css   # token Tailwind @theme — satu-satunya rumah nilai warna/ukuran di kode
config/semangat.php     # binding adaptor, kebijakan JP, tenggat evaluasi
tests/
  Unit/<Modul>/  Feature/<Modul>/  Scope/ (uji bocor per peran)  Arch/  Contract/ (adaptor)
docs/                   # blueprint ini
```
Setiap `<Modul>ServiceProvider` didaftarkan di `bootstrap/providers.php`, memuat `routes.php`, migrasi, dan views ber-namespace (`idp::…`).

## Penamaan
| Jenis | Konvensi | Contoh |
|---|---|---|
| Folder modul | PascalCase Bahasa Indonesia sesuai nama modul | `JpRiwayat` |
| Kelas, model | PascalCase Inggris (sesuai kolom "Kode" `docs/04_DOMAIN_MODEL.md`) | `IdpItem`, `Assessment` |
| Action | Kata kerja + objek | `SubmitIdp`, `ApproveIdpBatch`, `VerifyCertificate` |
| Tabel/kolom | snake_case Inggris + prefix domain | `pln_idp_items.target_level` |
| Route name | `<modul>.<resource>.<aksi>` | `idp.plans.show` |
| URL | Bahasa Indonesia, kebab-case | `/idp/rencana/{idp}`, `/verifikasi/{uuid}` |
| Livewire | `<Resource><Aksi>` | `IdpEditor`, `TeamApprovalList` |
| Tes | `<Fitur>Test` + `->group('<modul>')`; nama tes memuat ID kebutuhan | `it('IDP-10 menandai butir tanpa RHK')` |
| Teks UI | Bahasa Indonesia baku, via `lang/id/*.php` | `__('idp.submit')` |

## Lokasi jenis kode
- Logika bisnis **hanya** di `Actions/`/`Services/`; Livewire/Controller ≤ ~20 baris per method.
- Query cakupan data **hanya** lewat scope `visibleTo($user)` pada model (`docs/21_SECURITY_RULES.md`).
- Pemanggilan sistem luar **hanya** di `Modules/Integrasi/Adapters`.
- Pembuatan PDF **hanya** lewat `App\Support\DocumentIssuer`.
- Modul lain tidak meng-import `Models` modul lain untuk menulis; baca lewat relasi/Service publik.
