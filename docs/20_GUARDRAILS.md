# 20 — Guardrails

Larangan operasional keras untuk agen. Aturan keamanan rinci: `docs/21_SECURITY_RULES.md`.

**JANGAN:**
1. Menulis, mengirim, atau menyiapkan operasi tulis apa pun ke e-Kinerja (termasuk "sementara untuk uji").
2. Menyimpan, menghitung, atau menampilkan nilai/predikat SKP; menjadikan JP atau evaluasi bahan penilaian kinerja.
3. Mengedit data hasil integrasi (⇅) di luar adaptor M02, atau memberi peran manual yang seharusnya turunan.
4. Menampilkan data `indicative`/`provisional` dengan gaya `verified`.
5. Menulis query data pegawai tanpa scope `visibleTo($user)`; memakai `withoutGlobalScopes()` di kode non-admin.
6. Hard-code rahasia, URL sumber, atau token; semua lewat `.env` + `config/semangat.php`.
7. Menambah dependency Composer/NPM tanpa alasan tertulis di PR dan lewat `docs/22_CHANGE_POLICY.md`; menambah teknologi terlarang (`docs/09_STACK.md`).
8. Mengirim data pribadi ke layanan luar (AI, analytics, CDN, pastebin, log eksternal).
9. Memakai data produksi asli di dev/uji, atau menyalin dump produksi ke repo.
10. Menghapus/`skip()` tes, menurunkan level Larastan, atau menonaktifkan Pint untuk membuat CI hijau.
11. Mengerjakan kebutuhan berprioritas DAPAT, fitur AI, atau fase berikutnya tanpa instruksi (`docs/02_SCOPE.md`).
12. Menjalankan perintah ⚠️ (`migrate --force`, `migrate:fresh`, hapus berkas di bucket) terhadap lingkungan selain dev lokal.
13. Mengubah keputusan "DISEPAKATI" rancang bangun (D-01…D-10) walau tampak lebih baik — usulkan, jangan ubah.
