# Project Handoff Hub

Aplikasi produktivitas pribadi berbahasa Indonesia untuk menyimpan proyek GitHub, checkpoint kronologis, dan handoff Markdown yang akurat ke sesi ChatGPT/Codex berikutnya.

Laravel 13 · PHP 8.4+ · Blade · Livewire 4 · Tailwind CSS 4 · PostgreSQL · Socialite · Vite. Tidak ada frontend SPA terpisah atau ketergantungan API AI berbayar. Versi aktual dikunci di `composer.lock` dan `package-lock.json`.

## Fitur

- Login GitHub dengan OAuth state, allowlist ID numerik, session database terenkripsi, tanpa pendaftaran publik.
- Dashboard dari database; proyek dengan pencarian, status, prioritas, pengurutan, tag, arsip, sampah, dan pemulihan.
- Checkpoint append-only: branch, SHA, PR, implementasi, tes, CI, deployment, bug, blocker, tugas berikutnya, batasan keamanan, instruksi agen.
- Handoff deterministik dengan 13 bagian; pilih checkpoint historis, pratinjau, salin, buat ulang, unduh Markdown.
- Sinkronisasi GitHub baca saja: metadata, default branch, commit, PR terbuka, commit status, check runs, waktu sinkronisasi, dan peringatan data parsial.
- Backup JSON versi 1 dengan pratinjau, validasi, impor transaksional dan deduplikasi UUID. Snapshot API tidak diimpor sebagai bukti.

Data manual selalu diberi label. Aplikasi tidak menjalankan tes proyek sumber atau memverifikasi deployment. Tidak ada data contoh pada database produksi.

## Menjalankan lokal

Prasyarat: PHP 8.4+ dengan mbstring, XML/DOM, curl, zip, PDO SQLite/PostgreSQL; Composer 2; Node 24; PostgreSQL terisolasi atau SQLite untuk pengembangan.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Isi `.env` untuk database sendiri dan OAuth GitHub. Untuk pengembangan lokal dengan SQLite, buat `database/database.sqlite`, lalu set `DB_CONNECTION=sqlite` dan `DB_DATABASE` ke path absolut berkas tersebut. Jangan gunakan database proyek lain.

```sh
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Di GitHub → Settings → Developer settings → OAuth Apps, daftarkan callback `http://localhost:8000/auth/github/callback` dan homepage `http://localhost:8000`. Isi `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI`, dan `ALLOWED_GITHUB_IDS=118357175`. Tidak ada login alternatif saat OAuth belum dikonfigurasi. Gunakan origin yang sama saat membuka aplikasi dan mendaftarkan callback.

## Pemeriksaan

```sh
composer test
vendor/bin/pint --test
npm run build
php artisan route:list
php artisan migrate:status
composer audit
npm audit --audit-level=high
```

Tes PHPUnit secara default memakai SQLite `:memory:`. CI juga menjalankannya di PostgreSQL 17. Pengujian GitHub nyata bersifat opt-in: `RUN_GITHUB_LIVE_TEST=1 php artisan test --filter=GitHubLiveTest`; permintaan hanya GET ke repositori publik `laravel/laravel`.

### Browser QA

```sh
cp tests/Browser/environment.example .env.testing
# Ubah DB_DATABASE menjadi path absolut database/browser.sqlite di repositori ini.
php artisan key:generate --env=testing
php artisan handoff:browser-setup --env=testing
npm run build
npx playwright install chromium
# Pada terminal pertama:
APP_ENV=testing BROWSER_TEST=1 BROWSER_GITHUB_FAKE=1 php -S 127.0.0.1:8000 -t public tests/Browser/server.php
# Pada terminal kedua; gunakan executable browser yang terpasang:
PLAYWRIGHT_CHROMIUM_EXECUTABLE=/path/to/chromium npm run test:browser
```

Router QA memakai Socialite dengan state nyata dan respons token/identitas GitHub yang dimock. Permintaan API sinkronisasi browser dimock menjadi 404 untuk memeriksa error. Tidak ada bypass login yang dipasang di route aplikasi. Router hanya berjalan di environment testing dengan flag eksplisit; entry point produksi wajib `public/index.php`. QA menggunakan database SQLite khusus yang terpisah dari `.env` utama. Rerun dari database browser kosong agar nama fixture konsisten; hanya reset berkas `database/browser.sqlite` khusus QA.

## Dokumentasi

- [Arsitektur](docs/ARCHITECTURE.md)
- [Keamanan](docs/SECURITY.md)
- [Deployment dan konfigurasi eksternal](docs/DEPLOYMENT.md)
- [Bukti QA dan keterbatasan](docs/QA-REPORT.md)
- [Handoff pengembangan](docs/HANDOFF.md)

Produksi belum dinyatakan live. Lihat laporan QA dan deployment untuk blocker OAuth, koneksi Neon, hosting, dan visibilitas repositori.
