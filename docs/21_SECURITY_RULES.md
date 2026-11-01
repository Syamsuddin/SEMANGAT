# 21 — Security Rules

Pemilik keamanan (Bab 10 rancang bangun; OWASP ASVS L2 `[ASUMSI]`; UU 27/2022 PDP). Peran & matriks: `docs/05_USER_ROLE.md`. Skema & kolom 🔒: `docs/07_DATA_MODEL.md`.

## Autentikasi
- Login NIP + kata sandi (Fortify); adaptor `IdentityProvider` untuk SSO pemda kelak (FON-01).
- Kata sandi min. 12 karakter, cek kebocoran (`Password::uncompromised()`), hash bawaan Laravel.
- **2FA TOTP wajib** untuk `pengelola`, `clo`, `admin_teknis`, `auditor` (FON-06): middleware `EnsureTwoFactorForPrivilegedRoles` memblokir semua rute sampai 2FA dikonfirmasi.
- Rate limit login 5×/menit per NIP+IP, kunci 15 menit setelah 10 gagal; sesi idle 30 menit (peran 2FA: 15 menit); regenerasi session id saat login.
- Akun dinonaktifkan otomatis saat pegawai `inactive`.
- Aktivasi akun (keputusan pemilik produk 2026-11-01): pengelola memicu aktivasi, pegawai menerima tautan sekali pakai (dikirim/dicetak per OPD), wajib mengganti kata sandi saat pertama masuk; kata sandi awal tidak boleh NIP atau tanggal lahir.

## Otorisasi (diperiksa di server pada SETIAP request)
- Dua lapis wajib: (1) **Policy** per model untuk aksi (`IdpPolicy@approve` hanya atasan langsung/delegasi aktif); (2) **scope data** `visibleTo(User $user)` per model sesuai matriks S/T/O/A/B.
- Livewire: `authorize()` di setiap method aksi, bukan hanya `mount()`; properti publik berisi ID dikunci `#[Locked]`.
- Agregat untuk `clo`/`dewan_pengarah` tidak menampilkan baris individu; sel dengan n<5 disamarkan.
- Pengecualian `visibleTo` tertulis (keputusan pemilik produk 2026-11-01): pemilik IDP boleh menunjuk mentor lewat NIP pegawai aktif mana pun (`SaveIdpItem`); yang dikembalikan hanya id, dan nama mentor tampil pada IDP tersebut.
- Laporan asesmen rinci: izin `assessment.view_detail` + isian alasan → `sys_audit_logs.reason`.
- Catatan coaching: hanya coach & coachee; `shared_summary` hanya bila keduanya setuju.

## Validasi input
- Semua input lewat FormRequest/aturan Livewire `#[Validate]`; tidak ada `$request->all()` ke `create()` (pakai `validated()` + `$fillable` eksplisit).
- Unggahan: whitelist `pdf,jpg,png` (sertifikat) / `xlsx,csv` (impor), maks 10 MB, cek MIME nyata, nama berkas diganti UUID, simpan di object storage S3 (bukan `public/`). **Tanpa pemindai malware** (keputusan pemilik produk 2026-11-01, server Linux): pemeriksaan standar di atas wajib, berkas tidak pernah dieksekusi atau disajikan inline — diunduh sebagai lampiran dengan `Content-Type` hasil deteksi.
- Output Blade selalu `{{ }}`; `{!! !!}` dilarang kecuali HTML dari konten KMS yang sudah disanitasi (`mews/purifier` saat F8).

## Data sensitif & kriptografi
- TLS 1.2+ di semua lalu lintas; HSTS; cookie `secure`, `httponly`, `samesite=lax`.
- Kolom Sensitivitas Tinggi 🔒 dienkripsi cast `encrypted`; berkas laporan asesmen dienkripsi sisi server di bucket terpisah `semangat-sensitive`.
- **Audit wajib**: setiap pembacaan model berkolom 🔒 dan setiap tulis atas HasilUji/IDP/evaluasi dicatat lewat `App\Support\AuditLogger` (trait `AuditsSensitiveReads`). User DB aplikasi tidak punya hak UPDATE/DELETE pada `sys_audit_logs`.
- Rahasia hanya di `.env`/secret store server; `.env` tidak pernah di-commit; rotasi `APP_KEY` = operasi irreversibel (dekripsi ulang).

## PDP (UU 27/2022)
- Pembatasan tujuan: data hanya untuk pengembangan kompetensi; evaluasi individu tidak untuk penilaian kinerja.
- Minimisasi: adaptor hanya menarik atribut yang ada di `docs/07_DATA_MODEL.md`.
- Transparansi: halaman "Data saya" menampilkan seluruh data pegawai, label keyakinan, dan log siapa membuka laporan asesmennya.
- AI: tidak ada data pribadi ke layanan luar tanpa persetujuan tata kelola + perjanjian pemrosesan.
- Insiden: prosedur penanganan & pemberitahuan (penanggung jawab, pengambil keputusan, pemberitahuan ≤ 3×24 jam sesuai UU 27/2022) **wajib ditetapkan sebelum go-live**; agen yang menemukan kebocoran → hentikan & lapor.

## Operasional
- Lingkungan dev/uji/produksi terpisah; tanpa data produksi asli di dev.
- `composer audit` & `npm audit` di CI; uji penetrasi sebelum operasi produksi.
- Header keamanan: CSP (tanpa `unsafe-eval`; Livewire/Alpine dengan nonce), `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`.
