# 06 — Business Process

Pemilik alur. Peran: `docs/05_USER_ROLE.md`. Tabel/kolom: `docs/07_DATA_MODEL.md`. Siklus mengikuti periode SKP tahunan (tanggal rinci USULAN, menunggu jadwal KepLAN 604 & penganggaran).

## 1. Siklus tahunan
| Tahap | Waktu | Pelaku | Keluaran |
|---|---|---|---|
| 0 Berkelanjutan | sepanjang tahun | sistem, pengelola | sinkron pegawai/SKP; hitung ulang kesenjangan saat hasil uji baru |
| 1 Arah | akhir tahun sebelumnya | dewan, CLO | keputusan arah + BA forum strategis |
| 2 Perencanaan | akhir tahun s.d. awal periode | koord. OPD, Tim Pelaksana | HCDP disahkan |
| 3 IDP | awal periode SKP | asn, atasan | Rencana Pembelajaran PDF |
| 4 Pelaksanaan | sepanjang periode | asn, fasilitator | JP, sertifikat, artefak pengetahuan |
| 5 Pemantauan | triwulanan | atasan, koord. OPD | check-in IDP, keputusan forum operasional |
| 6 Pelaporan | akhir periode | asn, atasan | penutupan IDP, Laporan Hasil Pembelajaran, evaluasi L3/L4 |
| 7 Umpan balik | pergantian periode | sistem, CLO | bahan forum strategis berikutnya |

## 2. Sinkronisasi data sumber (M02)
1. Job terjadwal (harian 02:00 WITA `[ASUMSI]`) atau tombol pengelola memanggil adaptor sumber.
2. Adaptor menulis upsert idempoten berkunci `(source, external_id)`; setiap baris mengisi `source`, `synced_at`.
3. Pegawai yang hilang dari sumber/pensiun/pindah → `status=inactive`, akun dinonaktifkan; riwayat tetap.
4. Turunan dihitung ulang: `supervisor_id`, peran `atasan`, kesenjangan.
5. Hasil dicatat di `int_sync_runs` (sukses/gagal/jumlah) → panel kesehatan (INT-08).
- Gagal: run berstatus `failed`, retry 3× backoff, baris gagal ke antrean ulang; data lama tetap dipakai. Impor berkas dengan kolom wajib kosong → seluruh berkas ditolak dengan laporan per baris (tidak parsial).

## 3. Kesenjangan kompetensi (M03)
1. Ambil SKJ jabatan aktif pegawai → level dipersyaratkan.
2. Level aktual per kompetensi = hasil terbaik dengan prioritas keyakinan `verified` > `provisional` > `indicative`, lalu tanggal terbaru, dan **belum kedaluwarsa**.
3. `gap = required − actual` (>0 saja disimpan); `confidence` = label sumber level aktual. Tanpa data aktual sama sekali → gap = required, `indicative`.
4. Asesmen mandiri: asn mengisi → atasan memvalidasi → `provisional`. Ditolak atasan → tidak dipakai.

## 4. Siklus IDP (M06 — modul poros)
1. **Draf otomatis** (IDP-01): sistem mengusulkan ≤3 kompetensi dari gap terbesar, RHK SKP terkait, posisi kotak talenta, saldo JP.
2. **Lengkapi** (asn): pilih **2–3** kompetensi (IDP-02), level awal/target, tautkan tiap butir ke satu RHK, aktivitas 10:20:70, target JP, jadwal, indikator, mentor (opsional), aspirasi karier. Status `draft`.
3. **Ajukan** → `submitted`; atasan menerima notifikasi.
4. **Dialog & setuju** (atasan, tunggal atau massal dengan catatan) → `approved`; atau **kembalikan** dengan catatan → `draft`.
5. **Terbitkan Rencana Pembelajaran** (asn): PDF ber-QR + teks ekspektasi khusus "Kompeten" siap salin (template per jenjang `hr_positions.tier`: `docs/_sumber/KEBUTUHAN.md` § Lampiran C). Status `active`.
6. **Check-in** triwulanan menumpang dialog kinerja: progres per butir, catatan atasan.
7. **Penutupan** akhir periode: refleksi asn, bukti, penilaian penerapan atasan → `closed`; memicu Laporan Hasil Pembelajaran (M12).
8. **Agregasi**: butir IDP `approved` masuk agregasi kebutuhan HCDP siklus berikut.
- Gagal/cabang: butir tanpa tautan RHK → IDP tetap boleh diajukan tetapi **ditandai** (IDP-10) dan tampil di dashboard atasan/OPD. Butir ke-4 ditolak validasi; ajukan dengan <2 butir ditolak. Atasan berhalangan → delegasi (FON-08). Pegawai mutasi di tengah periode → IDP tetap, atasan baru menggantikan sebagai penyetuju check-in.
- Status: `draft → submitted → approved → active → closed` (+ `returned` kembali ke `draft`; `cancelled` hanya oleh pengelola beralasan).

## 5. HCDP ⇄ IDP (M05)
Kebutuhan muncul di ≥N IDP OPD (N=3 `[ASUMSI]`) tetapi tidak ada di HCDP → ditandai di HCDP; butir HCDP tanpa IDP pendukung → ditandai. Non-learning problem dicatat sebagai rekomendasi, tidak jadi program. Pengesahan: `draft → in_forum → minuted (BA) → ratified`.

## 6. JP & sertifikat (M12)
1. Otomatis: kelas selesai (M09), sesi coaching (M10), pembelajaran tempat kerja disetujui (M08) → `out_jp_records` status `verified`.
2. Manual: asn unggah sertifikat luar → `pending` → verifikator (pengelola/koord. OPD) `verified` / `rejected` dengan alasan.
3. Hanya `verified` dihitung ke saldo 20 JP. Duplikat (hash berkas sama, atau judul+tanggal+penyelenggara sama untuk pegawai sama) ditolak saat unggah.
4. Akhir periode: Laporan Hasil Pembelajaran dirakit dari IDP tertutup + JP + bukti → PDF ber-QR → asn mengunggah sendiri ke e-Kinerja.

## 7. Evaluasi pascapelatihan (M13)
1. Akhir program: L1 (3–5 pertanyaan) & L2 (pre/post) terisi.
2. T + 3 bulan: pengingat ke atasan & asn.
3. Atasan isi formulir ≤5 pertanyaan (ponsel); asn isi refleksi pasangan. Satu formulir L3 per program per pegawai.
4. Lewat tenggat (T + 3 bln + 30 hari `[ASUMSI]`) → status tampil di dashboard OPD.
5. Hasil masuk check-in IDP, Laporan, dan mutu program. Klaim dampak berbahasa "terkait", bukan "disebabkan".

## 8. Forum & keputusan (M04)
Kalender → bahan otomatis H-7 sesuai level → undangan + daftar hadir → notulen & keputusan (penanggung jawab, tenggat) → keputusan lewat tenggat otomatis masuk agenda berikutnya → eskalasi teknis → operasional → strategis. BA forum strategis dilampirkan pada pengesahan HCDP.
