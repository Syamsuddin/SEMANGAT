# Katalog Kebutuhan Fungsional & Aturan Bisnis

Ekstraksi verbatim Bab 5 *SEMANGAT CORPU Rancang Bangun v0.1* (29 Okt 2026). Berkas sumber pendukung (bukan bagian 28 dokumen): rumah tunggal teks kebutuhan `FON-01`…`EVA-10` yang dirujuk `docs/01_PRD.md`. Jangan diubah tanpa revisi rancang bangun; label status DISEPAKATI/USULAN/PERLU KONFIRMASI dihilangkan saat ekstraksi — lihat PDF asli `docs/_sumber/SEMANGAT-CORPU-Rancang-Bangun-v0.1.pdf` bila ragu.

## M01 Fondasi (`FON`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FON-01 | Masuk tunggal (SSO) dengan identitas pegawai berbasis NIP; mendukung integrasi dengan penyedia identitas pemda bila tersedia. | WAJIB |
| FON-02 | Model peran berbasis RBAC: ASN, atasan, koordinator OPD, fasilitator/pakar, pengelola Corpu, CLO, Dewan Pengarah, administrator teknis, auditor. | WAJIB |
| FON-03 | Peran turunan otomatis: status atasan diturunkan dari data jabatan; keanggotaan struktur Corpu diturunkan dari registri SK (M04). | WAJIB |
| FON-04 | Cakupan data per peran: diri sendiri, tim, OPD, kabupaten. | WAJIB |
| FON-05 | Jejak audit tak dapat diubah untuk aksi baca dan tulis atas data sensitif (hasil asesmen, IDP, evaluasi). | WAJIB |
| FON-06 | Autentikasi dua faktor wajib untuk peran pengelola, CLO, administrator teknis, dan auditor. | WAJIB |
| FON-07 | Pusat notifikasi dalam aplikasi, surel, dan kanal pesan instan yang dipakai pemda, dengan preferensi per pengguna. | SEBAIKNYA |
| FON-08 | Delegasi sementara hak persetujuan saat pejabat berhalangan, dengan masa berlaku dan jejak. | SEBAIKNYA |
| FON-09 | Verifikasi dokumen melalui kode QR untuk semua PDF yang diterbitkan aplikasi. | WAJIB |

**Aturan bisnis**

- Hak akses tidak pernah diberikan manual per orang bila dapat diturunkan dari jabatan atau SK.
- Akun pegawai yang pensiun, berhenti, atau pindah instansi dinonaktifkan otomatis berdasarkan data kepegawaian; riwayatnya tetap disimpan.

## M02 Integrasi sistem (`INT`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| INT-01 | Sinkronisasi data pegawai, unit organisasi, dan jabatan dari server pegawai lokal (yang menampung tarikan SIASN) melalui API. | WAJIB |
| INT-02 | Pembacaan data SKP: rencana hasil kerja, ekspektasi khusus, catatan dialog kinerja, dan realisasi, dari e-Kinerja atau sumber antara. Hanya baca. | WAJIB |
| INT-03 | Impor manual terkendali (berkas templat) sebagai jalur cadangan bila API SKP belum tersedia. | WAJIB |
| INT-04 | Pembacaan posisi kotak talenta dari sistem manajemen talenta. | SEBAIKNYA |
| INT-05 | Ekspor rekap JP dan riwayat bangkom ke sistem kepegawaian dan platform nasional sesuai format yang diminta. | SEBAIKNYA |
| INT-06 | Antarmuka penerusan undangan forum ke alur naskah dinas. | DAPAT |
| INT-07 | Dukungan standar interoperabilitas konten pembelajaran (SCORM atau xAPI) untuk konten dari pihak luar. | DAPAT |
| INT-08 | Panel kesehatan integrasi: waktu sinkron terakhir, jumlah gagal, dan antrean ulang. | WAJIB |

**Aturan bisnis**

- Arus SKP satu arah: SEMANGAT CORPU tidak menulis apa pun ke e-Kinerja.
- Setiap rekaman hasil integrasi menyimpan sumber dan waktu tarik sehingga asal datanya dapat ditelusuri.
- Bila sumber dan Corpu berbeda, sumber yang menang; koreksi dilakukan di sumber.

## M03 Profil kompetensi (`KOM`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| KOM-01 | Referensi SKJ per jabatan: kompetensi manajerial, sosiokultural, dan teknis beserta level yang dipersyaratkan. | WAJIB |
| KOM-02 | Kamus kompetensi teknis dari instansi pembina, dapat diperbarui. | WAJIB |
| KOM-03 | Profil individu: level aktual per kompetensi, metode (assessment center, uji kompetensi JF, asesmen mandiri), lembaga penguji, tanggal, dan masa berlaku. | WAJIB |
| KOM-04 | Mesin kesenjangan: membandingkan SKJ dengan profil dan menghasilkan daftar kompetensi yang kurang beserta besar selisihnya. | WAJIB |
| KOM-05 | Label keyakinan data: terverifikasi (uji resmi), sementara (asesmen mandiri divalidasi atasan), indikatif (disimpulkan dari SKP/dialog kinerja). | WAJIB |
| KOM-06 | Asesmen mandiri dengan validasi atasan bagi pegawai yang belum pernah diuji. | SEBAIKNYA |
| KOM-07 | Peringatan hasil uji yang mendekati atau melewati masa berlaku. | SEBAIKNYA |
| KOM-08 | Agregasi kesenjangan per OPD dan kabupaten untuk M05. | WAJIB |

**Aturan bisnis**

- Data berlabel indikatif atau sementara tidak boleh ditampilkan dengan gaya visual yang sama dengan data terverifikasi.
- Psikogram dan laporan asesmen rinci hanya dapat dibuka pengelola yang berwenang; atasan melihat ringkasan kesenjangan.
- Bila lapisan data bersama sudah siap, data SKJ dan hasil asesmen dibaca dari sana; modul ini hanya menampung sementara dengan batas API yang sudah dirancang.

## M04 Tata kelola: struktur dan forum (`TKL`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| TKL-01 | Registri SK struktur Corpu: nomor, tanggal, masa berlaku, unggahan dokumen. | WAJIB |
| TKL-02 | Keanggotaan Dewan Pengarah dan Tim Pelaksana dengan masa tugas; peringatan kursi kosong saat pejabat dimutasi. | WAJIB |
| TKL-03 | Pemetaan unsur struktur ke peran aplikasi (dipakai M01). | WAJIB |
| TKL-04 | Kalender forum tiga level: strategis (awal dan akhir tahun), operasional (triwulanan), teknis (bulanan atau per program). | WAJIB |
| TKL-05 | Undangan dan daftar hadir forum. | WAJIB |
| TKL-06 | Bahan forum otomatis sesuai level: strategis (tren kesenjangan, pemenuhan HCDP, dampak); operasional (JP per OPD, progres IDP, tingkat pengisian evaluasi); teknis (jadwal, kualitas konten, kendala). | WAJIB |
| TKL-07 | Notulen dan berita acara bertemplat; berita acara forum strategis menjadi lampiran pengesahan HCDP. | WAJIB |
| TKL-08 | Pelacak keputusan: penanggung jawab, tenggat, status; keputusan lewat tenggat otomatis masuk agenda forum berikutnya. | WAJIB |
| TKL-09 | Eskalasi berjenjang dari forum teknis ke operasional ke strategis dengan jejak lengkap. | SEBAIKNYA |
| TKL-10 | Repositori dokumen kebijakan Corpu dengan versi dan status (draf, berlaku, dicabut). | WAJIB |
| TKL-11 | Daftar periksa tujuh elemen Corpu dengan bukti dukung yang ditautkan otomatis dari modul lain. | SEBAIKNYA |
| TKL-12 | Ringkasan satu halaman (PDF atau tautan ponsel) untuk Dewan Pengarah menjelang forum strategis. | WAJIB |

**Aturan bisnis**

- Forum pembelajaran adalah mekanisme tata kelola, bukan ruang diskusi; diskusi informal ditempatkan di M10.
- Hanya forum resmi tiga level yang dicatat; rapat lain tidak diwajibkan masuk aplikasi.

## M05 Perencanaan HCDP (`HCD`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| HCD-01 | Penurunan arah pembelajaran dari RPJMD dan keputusan forum strategis. | WAJIB |
| HCD-02 | Analisis kesenjangan kompetensi tingkat OPD dari agregasi M03 dan IDP yang disetujui (M06). | WAJIB |
| HCD-03 | Pemilahan masalah pembelajaran dan bukan pembelajaran (learning dan non-learning problem). | WAJIB |
| HCD-04 | Penyusunan usulan kebutuhan pengembangan kompetensi OPD dengan formulir sesuai KepLAN 604. | WAJIB |
| HCD-05 | Konsolidasi HCDP kabupaten dari seluruh kontribusi OPD, termasuk penetapan level penyelenggaraan dan prioritas terhadap pagu. | WAJIB |
| HCD-06 | Alur pengesahan: draf, pembahasan forum, berita acara, pengesahan. | WAJIB |
| HCD-07 | Pemantauan realisasi HCDP: rencana dibandingkan program yang berjalan dan anggaran. | WAJIB |
| HCD-08 | Ekspor dokumen HCDP dan lampirannya ke format cetak. | SEBAIKNYA |

**Aturan bisnis**

- HCDP dan IDP saling mengoreksi: kebutuhan yang muncul di banyak IDP tetapi tidak ada di HCDP ditandai, dan sebaliknya.
- Masalah bukan pembelajaran tidak dipaksakan menjadi program pelatihan; dicatat sebagai rekomendasi ke pihak terkait.

## M06 Individual Development Plan (`IDP`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| IDP-01 | Draf IDP otomatis dari kesenjangan kompetensi (M03), SKP dan dialog kinerja (M02), posisi kotak talenta, riwayat bangkom, dan saldo JP. | WAJIB |
| IDP-02 | Isi IDP: 2 sampai 3 kompetensi prioritas dengan level awal dan target, tautan ke hasil kerja SKP, aktivitas 10:20:70, target JP, jadwal, indikator keberhasilan, atasan pendamping, dan mentor bila ada. | WAJIB |
| IDP-03 | Aspirasi karier diisi pegawai. | WAJIB |
| IDP-04 | Persetujuan atasan, termasuk tampilan ringkas per tim dan persetujuan massal dengan catatan. | WAJIB |
| IDP-05 | Penerbitan Rencana Pembelajaran (PDF ber-QR) dan teks ekspektasi khusus perilaku "Kompeten" siap salin ke e-Kinerja. | WAJIB |
| IDP-06 | Check-in berkala yang menumpang jadwal dialog kinerja SKP. | WAJIB |
| IDP-07 | Penutupan IDP: refleksi pegawai, bukti, dan penilaian atasan atas penerapan di pekerjaan. | WAJIB |
| IDP-08 | Variasi templat menurut kotak talenta: jalur akselerasi untuk potensi tinggi, IDP perbaikan untuk kinerja di bawah ekspektasi. | SEBAIKNYA |
| IDP-09 | Rekomendasi aktivitas dan konten berbasis AI, berstatus saran. | DAPAT |
| IDP-10 | Penanda IDP yang tidak tertaut ke hasil kerja SKP. | WAJIB |

**Aturan bisnis**

- Maksimal tiga kompetensi prioritas per IDP per periode.
- Pembelajaran tidak dijadikan RHK; masuk SKP melalui perilaku "Kompeten" dan ekspektasi khusus pimpinan.
- Pengecualian: pegawai yang tugasnya memang bangkom (misalnya di Bidang PSDM) dapat memiliki RHK terkait penyelenggaraan pembelajaran.
- Rekomendasi AI selalu dapat ditolak; keputusan tetap di pegawai dan atasan.

## M07 Strategi dan learning path (`LPA`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| LPA-01 | Learning path per jabatan atau rumpun jabatan dan per kelompok talenta. | WAJIB |
| LPA-02 | Penandaan setiap program dan konten dengan kompetensi dan level sasaran. | WAJIB |
| LPA-03 | Rekomendasi program dalam IDP berdasarkan tag kompetensi. | WAJIB |
| LPA-04 | Desain pembelajaran berbasis masalah nyata (action learning, proyek perubahan) yang menyelesaikan isu OPD. | SEBAIKNYA |
| LPA-05 | Kombinasi pendekatan individu, tim, dan organisasi dalam satu jalur. | SEBAIKNYA |
| LPA-06 | Visualisasi kemajuan pegawai pada jalurnya. | SEBAIKNYA |

**Aturan bisnis**

- Program tanpa tag kompetensi tidak dapat diterbitkan di katalog.

## M08 Sistem pembelajaran (katalog 10:20:70) (`KAT`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| KAT-01 | Katalog program: pelatihan, seminar, e-learning, coaching, mentoring, magang, job shadowing, detasering, penugasan, proyek. | WAJIB |
| KAT-02 | Klasifikasi tiap program ke porsi 10, 20, atau 70 dan jenis klasikal atau nonklasikal. | WAJIB |
| KAT-03 | Pencatatan pembelajaran di tempat kerja (porsi 70): penugasan, proyek, dan ekspektasi khusus pimpinan, dengan rujukan ke bukti dukung SKP. | WAJIB |
| KAT-04 | Pendaftaran, kuota, persetujuan, dan daftar tunggu. | WAJIB |
| KAT-05 | Program lintas OPD dan lintas instansi. | SEBAIKNYA |
| KAT-06 | Konversi JP sesuai ketentuan untuk setiap jenis program. | WAJIB |

**Aturan bisnis**

- Bukti dukung SKP yang sama dirujuk, bukan diunggah dua kali.
- Konversi JP mengikuti ketentuan yang berlaku dan dapat diatur oleh pengelola.

## M09 Learning Management System (`LMS`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| LMS-01 | Kelas daring dengan modul, video, dokumen, dan sesi sinkron. | WAJIB |
| LMS-02 | E-learning mandiri dengan pelacakan kemajuan. | WAJIB |
| LMS-03 | Pretest dan postest, kuis, penugasan, penilaian fasilitator. | WAJIB |
| LMS-04 | Presensi kelas tatap muka (kode QR atau daftar digital). | WAJIB |
| LMS-05 | Sertifikat ber-QR yang otomatis tercatat ke M12. | WAJIB |
| LMS-06 | Bank soal dengan peninjauan mutu; penanda skor rata-rata yang selalu mendekati sempurna. | SEBAIKNYA |
| LMS-07 | Akses ponsel yang nyaman dan hemat kuota. | WAJIB |
| LMS-08 | Impor konten SCORM/xAPI. | DAPAT |

**Aturan bisnis**

- Setiap kelas wajib memiliki tag kompetensi (dari M07) dan instrumen evaluasi level 1 dan 2 (dari M13).

## M10 Komunitas dan coaching (`KCO`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| KCO-01 | Komunitas praktik per rumpun jabatan dan tematik, dengan diskusi dan berbagi berkas. | WAJIB |
| KCO-02 | Pasangan coaching dan mentoring: penetapan, jadwal sesi, catatan sesi, target. | WAJIB |
| KCO-03 | Pencatatan JP dari sesi coaching dan mentoring sesuai ketentuan. | WAJIB |
| KCO-04 | Panen praktik baik dari diskusi ke KMS (M11) dengan satu klik dan kurasi. | SEBAIKNYA |
| KCO-05 | Forum tanya-jawab lintas OPD dengan penanda jawaban terbaik. | SEBAIKNYA |

**Aturan bisnis**

- Catatan sesi coaching bersifat rahasia antara coach dan coachee kecuali yang disepakati untuk dibagikan.

## M11 Manajemen pengetahuan (`KMS`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| KMS-01 | Repositori pengetahuan eksplisit: SOP, kajian, materi, regulasi internal. | WAJIB |
| KMS-02 | Penangkapan pengetahuan tacit: lesson learned, praktik baik, catatan serah terima jabatan. | WAJIB |
| KMS-03 | Direktori pakar internal (siapa yang paham apa). | WAJIB |
| KMS-04 | Alur kurasi dan validasi konten sebelum terbit. | WAJIB |
| KMS-05 | Pencarian teks penuh dengan penandaan kompetensi. | WAJIB |
| KMS-06 | Asisten pencarian berbasis AI atas korpus yang telah dikurasi, dengan rujukan sumber. | DAPAT |

**Aturan bisnis**

- Konten yang belum dikurasi ditandai jelas sebagai draf.
- Catatan serah terima jabatan diwajibkan oleh kebijakan, dilayani aplikasi.

## M12 Pencatatan JP dan riwayat bangkom (`JPR`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| JPR-01 | Rekap JP per pegawai per tahun dengan status pemenuhan 20 JP. | WAJIB |
| JPR-02 | Unggah sertifikat bangkom di luar aplikasi dan alur verifikasi. | WAJIB |
| JPR-03 | Pencatatan otomatis dari M08, M09, M10. | WAJIB |
| JPR-04 | Riwayat bangkom lengkap per pegawai. | WAJIB |
| JPR-05 | Penerbitan Laporan Hasil Pembelajaran di akhir periode: target dan realisasi IDP, rekap JP, bukti, refleksi pegawai, dan catatan atasan tentang penerapan di pekerjaan. | WAJIB |
| JPR-06 | Rekap per OPD dan kabupaten. | WAJIB |

**Aturan bisnis**

- Laporan Hasil Pembelajaran diunggah pegawai ke e-Kinerja sebagai bukti dukung perilaku "Kompeten"; Corpu tidak menulis ke e-Kinerja.
- Sertifikat yang diunggah tidak dihitung sebelum diverifikasi.

## M13 Evaluasi dan dampak (`EVA`)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| EVA-01 | Bank instrumen evaluasi: survei reaksi, templat pretest/postest, formulir evaluasi pascapelatihan atasan; ditempel otomatis ke setiap program. | WAJIB |
| EVA-02 | Level 1 Reaksi: survei 3 sampai 5 pertanyaan di akhir kegiatan. | WAJIB |
| EVA-03 | Level 2 Pembelajaran: skor pretest-postest dan penilaian fasilitator. | WAJIB |
| EVA-04 | Level 3 Perilaku: pengingat ke atasan 3 bulan setelah program; formulir maksimal 5 pertanyaan; refleksi pasangan dari pegawai. | WAJIB |
| EVA-05 | Level 4 Hasil: perbandingan profil kompetensi sebelum-sesudah, realisasi SKP pada hasil kerja yang tertaut IDP, indikator OPD untuk program strategis. | WAJIB |
| EVA-06 | Evaluasi sistem Corpu: capaian 20 JP, persentase IDP disetujui dan ditutup, pemenuhan HCDP, tren kesenjangan, status tujuh elemen. | WAJIB |
| EVA-07 | Dashboard berjenjang: pegawai, atasan, OPD, forum operasional, forum strategis. | WAJIB |
| EVA-08 | Rekomendasi ke siklus berikutnya: program dengan nilai level 3 rendah ditandai untuk didesain ulang; kesenjangan yang tidak mengecil naik prioritas. | SEBAIKNYA |
| EVA-09 | Kelompok pembanding sederhana untuk program besar. | DAPAT |
| EVA-10 | Rekap siap pakai untuk pelaporan ke LAN. | SEBAIKNYA |

**Aturan bisnis**

- Klaim dampak ditulis sebagai "terkait", bukan "disebabkan".
- Data evaluasi individu hanya untuk pengembangan, tidak untuk penilaian kinerja.
- Satu formulir level 3 per program per pegawai.
## Lampiran C — Usulan rumusan ekspektasi khusus perilaku "Kompeten" (USULAN)

Dipakai IDP-05 (teks siap salin), dipilih berdasarkan `hr_positions.tier`. Ekspektasi khusus tetap ditetapkan pejabat penilai (PermenPANRB 6/2022), sehingga teks dapat disunting pegawai sebelum disalin.

| `tier` | Rumusan |
|---|---|
| `pelaksana` | Menyusun IDP bersama atasan di awal periode, menyelesaikan aktivitas pengembangan yang direncanakan, dan menunjukkan penerapannya pada pekerjaan sebagaimana dilaporkan dalam Laporan Hasil Pembelajaran. |
| `fungsional` | Menyusun IDP yang selaras dengan standar kompetensi jabatan fungsionalnya, melaksanakan pengembangan yang direncanakan, dan membagikan sekurang-kurangnya satu praktik baik hasil pembelajaran ke manajemen pengetahuan instansi. |
| `pengawas` | Menyusun dan melaksanakan IDP sendiri, mendampingi penyusunan dan pelaksanaan IDP anggota tim, serta mengisi evaluasi pascapelatihan anggota tim tepat waktu. |
| `administrator_jpt` | Menyusun dan melaksanakan IDP sendiri, memastikan IDP seluruh pegawai di unitnya disusun dan dipantau, serta memanfaatkan hasil evaluasi pembelajaran dalam keputusan pengembangan unit. |
