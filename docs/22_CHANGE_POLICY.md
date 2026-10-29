# 22 — Change Policy

Pemilik kebijakan perubahan. Prinsip: **friksi sebanding irreversibilitas**.

## Git
- `main` terlindungi: hanya lewat PR; dilarang commit/force-push langsung.
- Branch: `feat/<fase>-<modul>-<ringkas>` (mis. `feat/f3-idp-approval`), `fix/<modul>-<ringkas>`, `docs/<ringkas>`.
- Commit Conventional Commits, scope = modul, sertakan ID kebutuhan: `feat(idp): persetujuan massal atasan (IDP-04)`.
- PR wajib: gerbang pra-commit hijau (`docs/11_COMMANDS.md`), daftar ID kebutuhan yang dipenuhi, tangkapan layar 360 px untuk UI pegawai/atasan.
- Merge ke `main`: pemilik produk (Syamsuddin) atau reviewer yang ditunjuk.

## Operasi irreversibel → gerbang manusia (berhenti, jelaskan dampak, tunggu "ya")
| Operasi | Syarat tambahan |
|---|---|
| Migrasi destruktif (drop/rename kolom/tabel, ubah tipe, hapus enum) | `migrate --pretend` dilampirkan; rencana data; backup |
| Ubah matriks akses, Policy, scope `visibleTo` | tes `tests/Scope` diperbarui untuk 9 peran |
| Ubah enkripsi, `APP_KEY`, atau kolom 🔒 | rencana re-enkripsi + backup |
| Hapus/anonimkan data, purge audit log | dasar retensi tertulis |
| Ubah Contract adaptor sumber (terutama yang menyentuh e-Kinerja) | konfirmasi tetap baca-saja |
| Tambah dependency / teknologi | alasan + alternatif yang ditolak |
| Ubah keputusan DISEPAKATI rancang bangun | persetujuan pemilik produk; catat di log keputusan |
| Rilis produksi | ikuti `docs/25_RELEASE_CHECKLIST.md` |

## Perubahan blueprint
Fakta berubah → ubah dokumen pemiliknya + `docs/_MANIFEST.json`, perbarui perujuk di `referenced_by`, jalankan `bash scripts/validate.sh`. Kode menang atas dokumen basi untuk fakta berpadanan kode (skema, perintah, struktur) — lapor & selaraskan.

## Rollback
- Kode: deploy ulang tag rilis sebelumnya.
- Skema: setiap migrasi punya `down()` yang teruji di staging; migrasi destruktif tidak bisa di-rollback → pulihkan dari backup pra-rilis.
- Fitur berisiko: feature flag di `config/semangat.php` (`features.*`) untuk mematikan tanpa deploy.
