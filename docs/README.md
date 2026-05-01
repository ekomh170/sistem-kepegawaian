# Dokumentasi Filament (Developer Notes)

Dokumen di folder ini ditulis buat developer internal biar gampang maintain aplikasi Filament tanpa harus ngulik ulang semua source dari nol.

## Isi Dokumentasi

1. [FILAMENT_CHEAT_SHEET.md](FILAMENT_CHEAT_SHEET.md)
   - Versi 1 halaman untuk kebutuhan harian developer
   - Ringkasan alur login, role, file kunci, dan command validasi
   - Cocok buat "lupa bentar, cek cepat"

2. [FILAMENT_DEVELOPER_PLAYBOOK.md](FILAMENT_DEVELOPER_PLAYBOOK.md)
   - Gambaran arsitektur Filament saat ini
   - Alur login, redirect, dan role access
   - Recipe "kalau mau ubah X, sentuh file Y"
   - Checklist validasi sebelum commit

3. [FILAMENT_FILE_MAP.md](FILAMENT_FILE_MAP.md)
   - Peta file Filament dan akses control yang ada sekarang
   - Daftar file per folder Resource, Widget, Panel, dan route
   - Fungsi setiap file secara ringkas

4. [FILAMENT_WORKFLOW_MERMAID.md](FILAMENT_WORKFLOW_MERMAID.md)
   - Cara kerja Filament end-to-end di project ini
   - Detail tanggung jawab file kunci per layer
   - Diagram Mermaid (component, sequence, activity)

## Cara Pakai Cepat

- Lagi mau orientasi cepat: buka `FILAMENT_CHEAT_SHEET.md` dulu.
- Lagi lupa file untuk ubah behavior tertentu: lanjut ke `FILAMENT_DEVELOPER_PLAYBOOK.md`.
- Lagi butuh lihat peta file secara lengkap: buka `FILAMENT_FILE_MAP.md`.
- Lagi butuh lihat alur runtime + diagram: buka `FILAMENT_WORKFLOW_MERMAID.md`.
- Habis edit, ikuti checklist validasi yang ada di playbook.

## Scope

Dokumentasi ini fokus ke:
- Panel Filament (`/admin`, `/supervisor`)
- Shared login (`/login`) dan redirect per role
- Resource, Widget, dan middleware role
- Integrasi halaman mobile karyawan yang nyambung ke auth Filament
