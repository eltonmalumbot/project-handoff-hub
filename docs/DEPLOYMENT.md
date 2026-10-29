# Deployment Laravel

## Status dan pilihan host

Target pertama: Laravel Cloud. Halaman resmi https://cloud.laravel.com/pricing diperiksa 29 Oktober 2026: Starter US$5/bulan ditambah pemakaian, termasuk kredit bulanan US$5 dan bulan pertama gratis. Harga/ketentuan dapat berubah; konfirmasi pada checkout. Tidak ada subscription, trial yang berujung berbayar, atau resource hosting berbayar yang diaktifkan. Tidak ada credential Laravel Cloud pada sesi ini. Produksi belum live dan belum ada URL deployment.

Alternatif yang siap digunakan: VPS atau host container dengan PHP 8.4, nginx, TLS, dan akses PostgreSQL. Dockerfile menyertakan nginx + PHP-FPM; session/cache berada di database, sehingga tidak bergantung pada disk lokal persisten. Node hanya membangun aset. Vercel tidak dipilih sebagai host PHP.

## Konfigurasi eksternal

1. Ubah repository `eltonmalumbot/project-handoff-hub` ke private di GitHub → Settings → General → Danger Zone jika diperlukan. Integrasi sesi ini tidak diizinkan mengubah visibilitas (403).
2. Daftarkan GitHub OAuth App. Homepage: URL HTTPS host final. Callback: persis `https://HOST/auth/github/callback`. Simpan client ID/secret di secret manager host. Set `ALLOWED_GITHUB_IDS=118357175`; jangan menggunakan username sebagai allowlist. OAuth tidak memerlukan scope repo.
3. Gunakan Neon project khusus `summer-mode-61001274` (`project-handoff-hub`), organisasi Elton Free, region Singapore `aws-ap-southeast-1`. Database `handoff_hub`; role awal `handoff_owner` untuk migrasi. Tidak ada schema aplikasi di Neon saat delivery karena koneksi TCP workspace ke port 5432 ditolak. Credential tersedia melalui Neon Console, tidak melalui Git atau dokumentasi ini. Jangan memakai database Supermoodle atau aplikasi lain.
4. Pilih branch khusus produksi dalam project terisolasi; gunakan host direct tanpa `-pooler` untuk migrasi. Runtime boleh memakai host pooled. Atur role runtime dengan izin tabel/sequences yang diperlukan dan role migrasi terpisah; jangan memberi runtime izin DDL/superuser. Jangan membuat resource yang dapat ditagih tanpa persetujuan pemilik.
5. Isi seluruh variabel dari `.env.example`. Produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://HOST`, APP_KEY baru dari `php artisan key:generate --show`, DB PostgreSQL dan credential, `DB_SSLMODE=verify-full`, `PGSSLROOTCERT` ke bundle CA valid host, `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`. Jangan merotasi APP_KEY setiap deploy.
6. Jika metadata repo privat diperlukan, tambah PAT fine-grained read-only terpisah sebagai `GITHUB_READ_TOKEN`; pilih hanya repo yang diperlukan. Public repo dan pengelolaan manual berfungsi tanpa PAT.

## Laravel Cloud setelah hosting disetujui

Hubungkan repo/branch release. Gunakan PHP 8.4 dan Node 24. Web root `public`, entry point Laravel standar. Masukkan secret pada environment produksi sebelum build/deploy. Build:

```sh
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Release pertama: jalankan migration memakai credential direct/migration role, lalu konfigurasi runtime memakai pooled/runtime role. Jalankan sebagai release command yang diserialkan oleh host:

```sh
php artisan migrate --force
php artisan optimize
php artisan handoff:production-check --database
```

Konfigurasi runtime harus dipulihkan sebelum `optimize` agar config cache tidak menyimpan credential role migrasi. Atur health check `/up`; cek database terpisah dengan production-check. Tidak perlu worker queue untuk MVP. Atur batas biaya sebelum aktivasi dan verifikasi harga final. Tidak ada deploy otomatis ke Laravel Cloud dalam GitHub Actions karena hosting belum terhubung.

## Host container alternatif

```sh
docker build -t handoff-hub:release .
# .env.production berisi secret; jangan commit.
docker compose up -d
# Jalankan migration dengan role migrasi/direct dari host rilis yang disetujui.
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
docker compose exec app php artisan handoff:production-check --database
```

Compose mengikat port hanya ke `127.0.0.1:8080`; terminasi TLS di reverse proxy host. `SSL_MODE=off` di container karena HTTPS diterminasi di proxy. `APP_URL` tetap HTTPS dan secure cookie tetap aktif. Hanya percayai forwarded headers dari proxy yang benar-benar dikendalikan host bila konfigurasi proxy diperlukan; jangan percaya semua proxy sembarangan. Handoff tidak memerlukan upload file persisten. Build di jaringan dengan proxy inspeksi TLS dapat memasok bundle CA sementara menggunakan BuildKit secret `--secret id=proxy_ca,src=/path/to/ca-bundle.pem`, tanpa memasukkannya ke layer image.

## Gerbang rilis dan verifikasi publik

Sebelum merge/rilis: PHPUnit SQLite dan PostgreSQL, Pint, build, audit dependency, serta browser 390/768/1440. Sebelum mengklaim produksi siap: `handoff:production-check --database` harus PASS seluruhnya, `/up` sehat, route privat redirect ke login, login GitHub asli berhasil untuk ID pemilik, akun non-owner ditolak, cookie secure/HttpOnly/Lax, tidak ada error 419 pada callback, CRUD/checkpoint/handoff/backup benar-benar tersimpan pada database produksi, sinkronisasi menangani repo privat/rate limit, unduhan tidak bocor antar sesi. Uji logout dan backup/restore dari record non-sensitif.

Rollback kode: deploy commit release sebelumnya; jangan otomatis rollback migration yang berpotensi menghilangkan data. Backup sebelum perubahan schema berikutnya; uji migration pada Neon branch terisolasi. Dokumentasikan SHA deployment dan hasil verifikasi aktual dalam laporan rilis, tanpa mengarang URL atau status live.
