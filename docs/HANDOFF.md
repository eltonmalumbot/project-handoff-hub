# Handoff pengembangan Project Handoff Hub

## Identifikasi

Repository: https://github.com/eltonmalumbot/project-handoff-hub
Branch implementasi: `feat/laravel-handoff-hub-mvp`.
Owner GitHub ID: `118357175`.
Bahasa UI: Indonesia. Zona waktu: Asia/Jakarta.

Gunakan Laravel 13/PHP 8.4/Livewire 4/Blade/Tailwind 4. Jangan migrasikan ke Next.js, React, atau backend Node. Versi tepat ada pada lockfile. Session/cache memakai database; OAuth Socialite tidak menyimpan token.

## Sudah diimplementasikan

Dashboard dengan angka database, CRUD/filter/sort proyek, arsip/sampah/pemulihan, checkpoint append-only, timeline, handoff deterministik 13 bagian dengan provenance, pilihan historis, copy/download/regenerate, API GitHub read-only dengan error/partial status, JSON backup versioned dengan preview dan deduplikasi, policy/middleware/Form Requests, CI, production-check, Dockerfile dan dokumentasi.

Taste Skill `design-taste-frontend` dipasang dari Leonxlnx/taste-skill melalui npx skills. Arah `framed-sheet`, seed `421114848`, IBM Plex Sans/Mono lokal. Panduan aplikasi dipakai pada desain dan QA. Lihat QA report untuk bukti serta keterbatasan.

## Bukti dan status

Lihat `docs/QA-REPORT.md` dan Actions PR untuk hasil tes terakhir; jangan menganggap daftar fitur sebagai bukti deployment. Commit/PR final tersedia pada riwayat GitHub. `git rev-parse HEAD` memberikan SHA checkout aktual; jangan menyalin SHA usang dari catatan ini.

Produksi belum live. Tidak ada client ID/secret OAuth produksi atau host Laravel terhubung. Neon project khusus `summer-mode-61001274` sudah dibuat pada org Free, tetapi migration belum berjalan di Neon karena TCP dari workspace ditolak. PostgreSQL 17 lokal terisolasi digunakan untuk pengujian. Repo awal publik; ubah private manual karena akses integrasi untuk mengubah visibility mendapat 403.

## Berikutnya

1. Periksa `git status`, PR, dan Actions aktual; jangan menimpa perubahan pengguna.
2. Selesaikan konfigurasi OAuth App dan hosting sesuai `docs/DEPLOYMENT.md`; konfirmasi biaya dengan pemilik sebelum aktivasi layanan berbayar.
3. Hubungkan DB Neon khusus ini, TLS verify-full, direct migration role dan role runtime terbatas; jalankan migration dari host yang dapat mengakses Neon.
4. Jalankan `php artisan handoff:production-check --database` pada konfigurasi produksi sebenarnya.
5. Verifikasi login GitHub asli, penolakan akun lain, session cookies, persistence, handoff dan backup melalui URL produksi. Catat URL dan SHA deploy hanya setelah verifikasi.

## Batasan keamanan

Jangan mencetak/mengomit `.env`, credential Neon, OAuth secret, token, atau log session. Jangan menyentuh Supermoodle atau database lain. Jangan menambah bypass autentikasi. Jangan menjalankan `tests/Browser/server.php` sebagai entry point produksi. Jangan menyatakan catatan manual sebagai hasil tes terverifikasi. Jangan membeli layanan tanpa persetujuan.

Fixture QA hanya ada di SQLite browser terpisah dan artefak test, bukan data produksi. Jangan impor fixture ini ke workspace pribadi pemilik.
