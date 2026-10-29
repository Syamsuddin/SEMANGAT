# 14 — Error Handling

Pemilik pola error.

## Klasifikasi
| Kelas | Contoh | Ke pengguna | Ke log |
|---|---|---|---|
| Validasi | NIP salah format, butir IDP ke-4, berkas >10 MB | pesan per field di bawah input (Bahasa Indonesia) | tidak |
| Aturan bisnis | ajukan IDP tanpa butir, verifikasi sertifikat yang sudah diverifikasi | toast `warning` + pesan spesifik | `info` |
| Otorisasi | akses data di luar cakupan | 403 halaman ramah "Anda tidak memiliki akses ke data ini" — **untuk data pegawai lain kembalikan 404** agar keberadaan data tidak bocor | `warning` + audit |
| Integrasi | sumber timeout, format berubah | panel kesehatan + notifikasi pengelola; pengguna biasa tetap melihat data terakhir dengan tanda "data per {synced_at}" | `error` + `int_sync_failures` |
| Job/PDF gagal | dompdf error | notifikasi "Dokumen gagal dibuat, coba lagi" + tombol ulang | `error`, job ke `failed_jobs` |
| Tak terduga | exception lain | halaman 500 generik + kode insiden (request id) | `critical` + stack trace |

## Aturan
- Aturan bisnis dilempar sebagai `App\Support\Exceptions\BusinessRuleException` (pesan sudah ramah pengguna, kunci `lang/id`); dirender di `bootstrap/app.php` → `withExceptions()`.
- Livewire: tangkap `BusinessRuleException` di component lalu `addError()`/toast; jangan biarkan jadi 500.
- Pesan: kalimat lengkap, sebut apa yang terjadi + apa yang bisa dilakukan ("IDP maksimal 3 kompetensi. Hapus salah satu untuk menambah yang baru.").
- **Dilarang**: menampilkan stack trace, SQL, path server, atau nilai 🔒 ke pengguna (`APP_DEBUG=false` di staging/produksi); menelan exception tanpa log; `catch (\Throwable)` kosong.
- Impor berkas: validasi seluruh berkas dulu; satu baris gagal → tolak berkas + laporan per baris yang dapat diunduh (tidak ada impor parsial).
- Setiap respons error membawa `X-Request-Id` yang sama dengan konteks log (`docs/15_OBSERVABILITY.md`).
