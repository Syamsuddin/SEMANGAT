# 18 — Repair Rules

Pemilik aturan perbaikan bug. Jebakan khusus proyek: `docs/16_DEBUGGING_GUIDE.md`.

## Alur
1. **Reproduksi** dengan tes gagal lebih dulu (Feature/Unit; untuk bocor data → `tests/Scope`). Tanpa reproduksi, jangan ubah kode.
2. **Akar masalah**: sebutkan file:baris dan mengapa; cek apakah bug berasal dari dokumen blueprint yang keliru → bila ya, berhenti & lapor (dokumen pemilik harus diperbarui, bukan diam-diam menyimpang).
3. **Perbaikan minimal** di lapisan yang benar (`docs/08_ARCHITECTURE.md`): aturan bisnis di Action/Service, bukan tambalan di Blade.
4. **Tes regresi** hijau + seluruh suite (`docs/11_COMMANDS.md` gerbang pra-commit).
5. Commit `fix(<modul>): <ringkas> (<ID kebutuhan>)`.

## Larangan
- Perbaikan tidak melebihi scope bug; refactor terpisah dan atas izin.
- Jangan "memperbaiki" dengan melonggarkan Policy/scope, menonaktifkan validasi, atau menghapus/`skip()` tes yang gagal.
- Jangan memperbaiki data ⇅ secara langsung di DB produksi; data sumber dikoreksi di sumber. Perbaikan data milik Corpu di produksi = migrasi data/command beraudit + gerbang manusia (`docs/22_CHANGE_POLICY.md`).
- Bug keamanan/kebocoran data: perlakukan sebagai insiden — hentikan, lapor ke pengguna sebelum memperbaiki (`docs/21_SECURITY_RULES.md`).
