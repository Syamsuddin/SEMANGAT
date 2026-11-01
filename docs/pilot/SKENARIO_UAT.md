# Skenario uji coba (UAT) SEMANGAT CORPU — OPD pilot

Panduan ini untuk perwakilan pegawai di 1–2 OPD pilot yang mencoba SEMANGAT CORPU sebelum dipakai luas. Uji coba dipandu Pengelola Corpu (Bidang PSDM). Catat setiap kendala di **Formulir temuan** (`docs/pilot/FORMULIR_TEMUAN.md`).

## Persiapan (oleh Pengelola Corpu)
1. Tentukan OPD pilot dan siapkan akun uji untuk tiap peran di tabel berikut. Satu orang boleh memegang lebih dari satu peran, **kecuali** akun untuk S9 (lihat catatan di bawah tabel).

   | Peran | Siapa | Catatan |
   |---|---|---|
   | ASN | 3–5 pegawai OPD pilot | sebaiknya ada pelaksana, fungsional, dan pengawas |
   | Atasan langsung | atasan dari ASN di atas | peran muncul otomatis dari data jabatan |
   | Koordinator OPD | 1 orang per OPD pilot | ditugaskan pengelola |
   | Pengelola | staf Bidang PSDM | wajib verifikasi dua langkah (2FA) |
   | CLO / Dewan Pengarah | 1 orang (bila ada) | hanya melihat agregat |

   Cakupan akun berperan ganda digabungkan: ASN yang juga koordinator OPD boleh melihat rekan satu OPD, dan CLO yang juga pengelola atau auditor melihat angka tanpa penyamaran. Untuk S9 siapkan akun **berperan tunggal**: (a) ASN tanpa peran koordinator, pengelola, atau auditor, beserta satu pegawai sasaran yang bukan bawahan langsungnya; (b) koordinator OPD tanpa peran pengelola atau auditor, beserta satu pegawai sasaran di OPD lain; (c) CLO atau Dewan Pengarah tanpa peran pengelola atau auditor. Jangan mengubah peran turunan (atasan) secara manual untuk keperluan ini.

2. Pastikan data OPD pilot sudah masuk: pegawai, jabatan, SKJ, dan hasil uji (bila ada), serta berkas SKP periode berjalan.
3. Pastikan setiap penguji sudah bisa masuk. Aktivasi akun adalah blocker B-1 di catatan rilis, jadi selesaikan dulu sebelum hari uji coba.
4. Siapkan **dua** berkas sertifikat contoh (PDF atau foto JPG/PNG) untuk tiap ASN penguji, dengan isi berkas **dan** identitas kegiatan (nama kegiatan, tanggal mulai, penyelenggara) yang berbeda. Berkas kedua dipakai di S6.5; memakai ulang berkas pertama akan ditolak sebagai duplikat.

## Cara menguji
- Gunakan **ponsel** untuk peran ASN dan atasan, dan **komputer** untuk koordinator OPD dan pengelola.
- Jalankan skenario berurutan. Skenario 3–8 membentuk satu siklus.
- Pada setiap langkah, bandingkan yang terjadi dengan kolom **Hasil yang diharapkan**. Bila berbeda, isi formulir temuan dengan nomor langkahnya (mis. "S4.3").
- Di ponsel, perhatikan juga: tidak ada teks terpotong, tidak perlu menggeser ke samping, dan tombol mudah disentuh.

---

### S1 — Masuk dan beranda (semua peran)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1.1 | Buka alamat SEMANGAT CORPU, masuk dengan NIP dan kata sandi. | Beranda tampil sesuai peran Anda. |
| 1.2 | (Pengelola, CLO) Masuk pertama kali. | Diminta mengaktifkan verifikasi dua langkah sebelum bisa membuka halaman lain. |
| 1.3 | (ASN) Lihat beranda. | Ada ringkasan profil dan kotak **Saldo JP** tahun ini. |
| 1.4 | Buka **Profil saya**. | Data kepegawaian sesuai. Bila ada yang keliru, catat sebagai temuan; perbaikannya dilakukan di sistem kepegawaian, bukan di aplikasi ini. |

### S2 — Profil kompetensi (ASN, atasan)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 2.1 | (ASN) Buka menu **Kompetensi**. | SKJ jabatan tampil dengan level aktual dan kesenjangan. Tiap level berlabel **Terverifikasi**, **Sementara**, atau **Indikatif**, dan labelnya dibedakan juga dengan garis dan ikon, bukan warna saja. |
| 2.2 | (ASN tanpa hasil uji) Isi **Asesmen mandiri**, lalu kirim. | Asesmen menunggu validasi atasan. |
| 2.3 | (Atasan) Buka **Validasi asesmen**, setujui satu, kembalikan satu dengan alasan. | Yang disetujui menjadi berlabel **Sementara**. Pegawai yang dikembalikan melihat alasannya. |
| 2.4 | (Atasan) Buka **Kompetensi tim**. | Ringkasan kesenjangan bawahan langsung tampil, tanpa laporan rinci/psikogram. |

### S3 — Menyusun dan mengajukan IDP (ASN)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 3.1 | Buka **IDP saya**, tekan **Susun IDP**. | Draf berisi hingga 3 kompetensi dengan kesenjangan terbesar, lengkap dengan usulan tautan RHK SKP. |
| 3.2 | Pastikan draf berisi 3 kompetensi (tambah bila kurang), lalu cari formulir tambah kompetensi. | Formulir tambah kompetensi tidak tersedia lagi; IDP maksimal 3 kompetensi. |
| 3.3 | Atur level awal → target, tautkan tiap butir ke RHK, tambah minimal satu aktivitas 10:20:70 dengan JP dan jadwal. | Tersimpan, ada pemberitahuan sukses. |
| 3.4 | Pada satu butir, kosongkan pilihan RHK lalu tekan **Simpan** pada butir itu. Setelah tersimpan, tekan **Ajukan**. | IDP terkirim; butir tersebut bertanda **Belum tertaut SKP**. (Bila pilihan dikosongkan tanpa **Simpan**, tautan lama tetap dipakai.) |

### S4 — Persetujuan IDP (atasan)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 4.1 | Buka **IDP tim**. | IDP bawahan yang diajukan tampil. |
| 4.2 | Centang beberapa (atau **Pilih semua**), tulis satu catatan, **Setujui**. | Semua yang dipilih disetujui. Selesai dalam **kurang dari 3 menit** dari ponsel; catat waktunya. |
| 4.3 | (ASN) Buka lonceng **Notifikasi** di kanan atas. | Ada notifikasi "IDP disetujui". |

### S5 — Rencana Pembelajaran (ASN)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 5.1 | Di **IDP saya**, pilih **Terbitkan Rencana Pembelajaran** dan konfirmasi. | Muncul pesan bahwa Rencana sedang diterbitkan. |
| 5.2 | Tunggu notifikasi "Rencana Pembelajaran Anda sudah terbit", lalu muat ulang halaman. | Tombol **Unduh Rencana Pembelajaran** tampil. PDF berisi kompetensi, tautan RHK, aktivitas, target JP, label keyakinan level awal, dan kode QR. Bila yang datang notifikasi "gagal dibuat, coba lagi", catat sebagai temuan lalu tekan **Terbitkan ulang**. |
| 5.3 | Pindai QR di PDF dengan ponsel lain (tanpa masuk). | Halaman verifikasi menampilkan jenis dokumen, nama, tanggal, dan status, **tanpa** isi dokumen. |
| 5.4 | Salin teks ekspektasi "Kompeten" dengan tombol **Salin**. | Teks tersalin dan siap ditempel sendiri di e-Kinerja. |

### S6 — JP dan sertifikat (ASN, koordinator OPD)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 6.1 | (ASN) Buka **JP saya**, isi **Unggah sertifikat bangkom luar**, lampirkan berkas contoh, **Unggah**. | Sertifikat tampil di riwayat berstatus **Menunggu verifikasi**. Saldo belum bertambah. |
| 6.2 | (ASN) Unggah berkas yang sama sekali lagi. | Ditolak: "Sertifikat ini sudah pernah diunggah". |
| 6.3 | (Koordinator OPD) Buka **Verifikasi sertifikat**, unduh berkasnya, lalu **Setujui** (koreksi JP bila perlu). | Sertifikat hilang dari antrean. |
| 6.4 | (ASN) Buka **JP saya** lagi. | Saldo bertambah, mis. "12 dari 20 JP · Belum terpenuhi". Ada notifikasi sertifikat diverifikasi. |
| 6.5 | (ASN) Unggah berkas sertifikat **kedua** (berbeda isi dan identitas kegiatan dari yang pertama). (Koordinator) **Tolak** tanpa alasan, lalu dengan alasan. | Tanpa alasan ditolak sistem. Dengan alasan, pegawai melihat alasannya di riwayat dan notifikasi. |
| 6.6 | (Koordinator OPD) Buka **Rekap JP**. | Hanya pegawai OPD Anda yang tampil, beserta status pemenuhan 20 JP. |
| 6.7 | (Atasan) Buka profil bawahan dari menu **Tim**, lalu **Lihat saldo & riwayat JP**. | Saldo dan riwayat bawahan tampil, sertifikatnya dapat diunduh; tidak ada formulir unggah. |

### S7 — Check-in triwulan (ASN, atasan)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 7.1 | (ASN) Di **IDP saya**, isi progres tiap kompetensi dan catatan, simpan check-in. | Check-in triwulan berjalan tercatat. |
| 7.2 | (Atasan) Di **IDP tim**, tambahkan catatan atasan pada check-in itu. | Catatan tampil di IDP pegawai. |

### S8 — Penutupan IDP dan Laporan Hasil Pembelajaran (ASN, atasan)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 8.1 | (ASN) Tulis **refleksi** penutupan, simpan. | Refleksi tersimpan. |
| 8.2 | (Atasan) Di **IDP tim**, isi penilaian penerapan di pekerjaan, **Tutup IDP**. | IDP berstatus tertutup. |
| 8.3 | (ASN) Di **IDP saya** (atau **JP saya** pada tahun IDP), pilih **Terbitkan Laporan Hasil Pembelajaran**. | Muncul pesan bahwa laporan sedang diterbitkan; notifikasi menyusul. |
| 8.4 | Buka notifikasi "Laporan Hasil Pembelajaran Anda sudah terbit", lalu unduh laporan. | Notifikasi membuka **JP saya** pada tahun IDP dengan tombol **Unduh Laporan Hasil Pembelajaran**. | PDF ber-QR berisi target dan realisasi IDP, rekap JP, bukti sertifikat terverifikasi, refleksi, dan catatan atasan. Berkas ini yang Anda unggah sendiri ke e-Kinerja. |

### S9 — Batas akses (semua peran)
| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 9.1 | (Akun S9-a: ASN berperan tunggal) Minta pengelola memberi tahu ID pegawai sasaran yang bukan bawahan langsung Anda, lalu buka `/jp/pegawai/{id}` dan `/pegawai/{id}`. | Ditolak atau tidak ditemukan. Data pegawai sasaran tidak tampil. |
| 9.2 | (Akun S9-b: koordinator OPD tanpa peran pengelola/auditor) Buka data pegawai sasaran di OPD lain. | Ditolak. |
| 9.3 | (Akun S9-c: CLO/Dewan tanpa peran pengelola/auditor) Buka **Rekap JP** dan **Agregat kesenjangan**. | Hanya angka per OPD tanpa nama. Kelompok di bawah 5 pegawai tampil sebagai "< 5"; di Rekap JP, OPD lain dapat ikut bertanda "Disamarkan" agar kelompok kecil tidak dapat dihitung dari total. |

---

## Kriteria lulus uji coba
- Tidak ada temuan berkategori **Penghalang** yang masih terbuka.
- Persetujuan IDP massal oleh atasan selesai < 3 menit dari ponsel (S4.2).
- Seluruh skenario S3–S8 dapat diselesaikan dari ponsel oleh ASN dan atasan.
- Temuan **Berat** sudah punya rencana perbaikan sebelum F5 dimulai (keputusan Q-11).
