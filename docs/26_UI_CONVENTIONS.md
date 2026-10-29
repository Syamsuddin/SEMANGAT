# 26 — UI Conventions

Rumah tunggal fakta antarmuka. **Nilai warna/ukuran/font di luar tabel token TERLARANG** — di kode, nilai ini hanya hidup di blok `@theme` `resources/css/app.css`.

## Jangkar
Identitas visual dokumen *Rancang Bangun v0.1*: navy + oranye + teal, Inter, di atas Tailwind CSS 4.

## Token
| Kategori | Nama (`@theme`) | Nilai | Pakai untuk |
|---|---|---|---|
| Warna | `--color-navy` | `#0F2A4A` | header, sidebar, judul, tombol primer |
| Warna | `--color-navy-700` | `#1D3F66` | hover tombol primer |
| Warna | `--color-teal` | `#14857F` | isian/ikon/garis (warna identitas) — **tidak untuk teks** (4,48:1) |
| Warna | `--color-teal-700` | `#0F716C` | teks teal: tautan, tombol sekunder, badge Terverifikasi (5,84:1) |
| Warna | `--color-teal-50` | `#E6F4F3` | latar badge Terverifikasi |
| Warna | `--color-orange` | `#F2A33A` | aksen: garis, progres, isian badge Sementara — **tidak untuk teks** |
| Warna | `--color-orange-50` | `#FDF3E4` | latar badge Sementara |
| Warna | `--color-ink` | `#1B2430` | teks utama |
| Warna | `--color-muted` | `#5B6675` | teks sekunder, badge Indikatif |
| Warna | `--color-line` | `#E2E6EC` | border, pemisah |
| Warna | `--color-canvas` | `#F7F8FA` | latar halaman |
| Warna | `--color-surface` | `#FFFFFF` | kartu, panel, input |
| Semantik | `--color-success` | `#2E7D32` | sukses |
| Semantik | `--color-warning` | `#8F5600` | peringatan (teks, 6,0:1) |
| Semantik | `--color-danger` | `#C62828` | bahaya, error validasi |
| Semantik | `--color-info` | `#1565C0` | info |
| Tipografi | `--font-sans` | `"Inter", ui-sans-serif, system-ui, sans-serif` (Inter di-host sendiri, `public/fonts`) | semua |
| Tipografi | skala | 12/16 · **14/20 (dasar)** · 16/24 · 20/28 (H3) · 24/32 (H2) · 30/36 (H1); bobot 400/500/600/700 | |
| Spasi | basis | 4 px (kelipatan Tailwind bawaan); jarak antar-kartu 16 px, padding kartu 16 px (ponsel) / 24 px (≥1024) | |
| Radius | `--radius-md` / `--radius-lg` | 8 px (input, tombol, badge) / 12 px (kartu, modal) | |
| Bayangan | `--shadow-card` | `0 1px 2px rgb(15 42 74 / 0.08)` — satu level saja | kartu |
| Fokus | ring | 2 px `--color-teal-700`, offset 2 px | semua elemen interaktif |
| Sentuh | target min | 44×44 px | ponsel |

Kontras (dihitung terhadap `surface` dan `canvas`, juga latar badge `teal-50`/`orange-50`): `ink`, `navy`, `teal-700`, `muted`, `danger`, `warning`, `info`, `success` ≥ 4,5:1 (AA teks normal). `teal` dan `orange` < 4,5:1 sehingga tidak boleh menjadi warna teks atau satu-satunya pembeda.

## Label keyakinan (KOM-05 — bukan hanya warna)
| Label | Komponen | Gaya |
|---|---|---|
| Terverifikasi | `<x-confidence status="verified"/>` | latar `teal-50`, teks `teal-700`, border solid 1 px `teal`, ikon `check-badge` |
| Sementara | `status="provisional"` | latar `orange-50`, teks `ink`, border **putus-putus** `orange`, ikon `clock` |
| Indikatif | `status="indicative"` | latar `surface`, teks `muted` *miring*, border **titik-titik** `muted`, ikon `light-bulb`, tooltip "Disimpulkan dari SKP, bukan hasil uji" |
Nilai kompetensi berlabel sementara/indikatif tidak boleh memakai angka tebal seperti terverifikasi.

## Inventaris komponen (`resources/views/components/`)
| Komponen | Varian |
|---|---|
| `x-button` | primary (navy), secondary (outline & teks `teal-700`), danger, ghost; size md/sm; state loading |
| `x-input`, `x-select`, `x-textarea`, `x-file-upload` | dengan label wajib, hint, pesan error `danger` |
| `x-card`, `x-stat-tile` | stat tile: angka + label + progres (mis. saldo JP) |
| `x-table` | tabel responsif → kartu bertumpuk di < 768 px; kolom checkbox untuk aksi massal |
| `x-badge` | status IDP/JP: netral, info, success, warning, danger |
| `x-confidence` | lihat atas |
| `x-modal`, `x-confirm-dialog` | konfirmasi aksi destruktif/irreversibel |
| `x-toast` | success/warning/danger, hilang 5 dtk, dapat ditutup |
| `x-empty-state`, `x-skeleton`, `x-error-state` | empat state |
| `x-progress` | progres IDP/JP (isian `orange`, jalur `line`) |
| `x-nav` | bottom nav 4 item (ponsel, pegawai/atasan) · sidebar `navy` (≥1024, pengelola) |
Ikon: Heroicons saja (outline 24 umum, solid 20 di badge).

## Peta halaman → pola (MVP)
| Halaman | Pola | Perangkat utama |
|---|---|---|
| Masuk, setup 2FA | autentikasi | semua |
| Beranda per peran (12.2: pegawai, atasan, koord. OPD) | dasbor | ponsel (pegawai/atasan), lebar (OPD) |
| Profil & kompetensi saya, "Data saya" | detail | ponsel |
| Asesmen mandiri / validasi | formulir | ponsel |
| Susun/ubah IDP | formulir bertahap (3 langkah) | ponsel |
| Persetujuan IDP tim | daftar+filter + aksi massal | ponsel |
| Detail IDP, check-in, penutupan | detail + formulir | ponsel |
| Saldo JP & riwayat, unggah sertifikat | dasbor + daftar + formulir | ponsel |
| Verifikasi sertifikat (verifikator) | daftar+filter | lebar |
| Data pegawai, panel integrasi, impor | daftar+filter, dasbor | lebar |
| Verifikasi dokumen publik `/verifikasi/{uuid}` | detail (tanpa login) | semua |
Aksi destruktif/irreversibel (hapus butir, tolak, batalkan IDP, cabut dokumen, jalankan impor) selalu lewat `x-confirm-dialog`.

## Perangkat & breakpoint
Mobile-first. Wajib layak: **360 px** (pegawai, atasan), 768 px, ≥1024 px (pengelola/perencana dioptimalkan). Breakpoint Tailwind bawaan `sm 640 · md 768 · lg 1024 · xl 1280`.

## Empat state wajib (setiap halaman/komponen ber-data)
Kosong (`x-empty-state`: ikon + kalimat + satu tindakan, mis. "Belum ada IDP. Susun IDP"), memuat (`x-skeleton`, `wire:loading`), gagal (`x-error-state` + "Coba lagi"), sukses (`x-toast` + status jelas "apa yang menunggu saya").

## Mikroteks
Bahasa Indonesia baku, sapaan "Anda", nada ramah-formal dinas. Kata kerja tombol konsisten: **Simpan, Ajukan, Setujui, Kembalikan, Terbitkan, Unggah, Batal, Hapus**. Tanggal `29 Okt 2026`, waktu `14.30 WITA`, angka `1.234,5`. Istilah IDP/HCDP/JP/SKJ pertama kali tampil dengan tooltip penjelasan (definisi: `docs/04_DOMAIN_MODEL.md`).

## Aksesibilitas
WCAG 2.2 AA untuk antarmuka pegawai (NFR-07): setiap input berlabel, fokus keyboard terlihat (ring token), urutan tab logis, `aria-live` untuk toast, target sentuh 44 px, tidak ada informasi yang hanya disampaikan warna.

## Larangan UI
Nilai di luar token · gradien, glassmorphism, bayangan bertingkat · emoji di antarmuka · teks placeholder/lorem tersisa · ikon selain Heroicons · animasi dekoratif (hanya transisi ≤150 ms fungsional) · pustaka UI/komponen baru tanpa `docs/22_CHANGE_POLICY.md` · font eksternal via CDN.
