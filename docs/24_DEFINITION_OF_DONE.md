# 24 — Definition of Done

Berlaku untuk setiap task (Bab 15.3 rancang bangun + konvensi blueprint).

- [ ] Kriteria terima fitur di `docs/23_ACCEPTANCE_CRITERIA.md` terpenuhi; nama tes memuat ID kebutuhan.
- [ ] Aturan bisnis terkait (Bab 5 rancang bangun) dites otomatis dan hijau.
- [ ] Gerbang pra-commit hijau: Pint, Larastan level 6, seluruh tes (`docs/11_COMMANDS.md`); tanpa tes di-skip baru.
- [ ] Lolos cek cakupan data per peran (`tests/Scope`) bila menyentuh data pegawai.
- [ ] Aksi baca/tulis data sensitif tercatat di `sys_audit_logs`.
- [ ] Fitur pegawai/atasan tampil & dapat diselesaikan di 360 px; empat state UI ada (`docs/26_UI_CONVENTIONS.md`).
- [ ] Struktur & penamaan sesuai `docs/12_PROJECT_STRUCTURE.md`; tidak melanggar `docs/20_GUARDRAILS.md` / `docs/21_SECURITY_RULES.md`.
- [ ] Migrasi punya `down()` dan `php artisan migrate:fresh --seed` berhasil.
- [ ] Teks UI baru ada di `lang/id`; panduan pengguna singkat (1 paragraf/peran) ditambahkan di `docs/user/<modul>.md`.
- [ ] Bila fakta blueprint berubah: dokumen pemilik + `_MANIFEST.json` diperbarui, `bash scripts/validate.sh` tanpa `[FAIL]`.
