# 03 — Roadmap

Status: **USULAN** — tahapan final (Q-11) sengaja ditunda di rancang bangun. Urutan mengikuti graf dependensi modul: tiap fase adalah **vertical slice** (migrasi → Action/Service → Livewire UI → tes) yang bisa didemokan ke pengguna nyata.

| Fase | Tujuan demoable | Modul / kebutuhan tercakup | Bergantung | Status |
|---|---|---|---|---|
| F1 Fondasi & data pegawai | Pengelola mengimpor pegawai/unit/jabatan; ASN login NIP dan melihat profil sendiri; atasan otomatis melihat timnya; panel kesehatan sinkron tampil | FON-01…06, 09 (verifikator QR publik); INT-01, 03, 08; peran turunan & scope | — | **Selesai** (2026-10-29) |
| F2 Profil & kesenjangan | ASN melihat SKJ jabatannya, level aktual, dan kesenjangan berlabel keyakinan; atasan memvalidasi asesmen mandiri | KOM-01…05, 06, 08; INT-02 (impor berkas SKP) | F1 | Belum |
| F3 IDP poros | ASN menyusun IDP dari draf otomatis, tautan SKP, atasan setuju massal, Rencana Pembelajaran PDF ber-QR terbit, check-in, penutupan | IDP-01…07, 10 | F2 | Belum |
| F4 JP & Laporan | Saldo 20 JP, unggah + verifikasi sertifikat luar, riwayat bangkom, Laporan Hasil Pembelajaran PDF | JPR-01…06; FON-07 (notifikasi dasar) | F3 | Belum |
| F5 Katalog, path & LMS | Katalog 10:20:70 bertag kompetensi, pendaftaran, kelas e-learning, presensi QR, sertifikat otomatis ke M12, rekomendasi program ke IDP | LPA-01…03; KAT-01…04, 06; LMS-01…05, 07 | F4 | Belum |
| F6 Evaluasi & dashboard | L1/L2 otomatis di akhir program, pengingat L3 T+3 bln, dashboard pegawai/atasan/OPD | EVA-01…07 | F5 | Belum |
| F7 Tata kelola & HCDP | Registri SK, forum 3 level dengan bahan otomatis H-7, pelacak keputusan, HCDP OPD → kabupaten → pengesahan | TKL-01…08, 10, 12; HCD-01…07 | F6 | Belum |
| F8 Sosial & pengetahuan | Komunitas praktik, pasangan coaching (catatan rahasia), KMS dengan kurasi & pencarian | KCO-01…03; KMS-01…05 | F5 | Belum |
| Pasca | Kebutuhan SEBAIKNYA tersisa; DAPAT/AI hanya atas keputusan | sisa | — | Belum |

**MVP = F1–F4.** Aturan: jangan memulai fase berikutnya sebelum blok verifikasi fase aktif di `docs/23_ACCEPTANCE_CRITERIA.md` lulus.
