# Laporan QA

Tanggal: 29 Oktober 2026. Semua hasil di bawah berasal dari perintah yang dijalankan. Hasil lokal, API nyata, fixture browser, dan produksi dibedakan.

## Lingkungan dan hasil

PHP 8.4.27 dalam container terisolasi; Laravel 13.39.0; Livewire 4.4.8; Socialite 5.31.0; Pint 1.32.1; PHPUnit 12.5.42; Vite 8.3.5; Tailwind CSS 4; Node 24.19.0; Chromium sistem; PostgreSQL 17.11 lokal terisolasi.

| Pemeriksaan | Hasil aktual |
| --- | --- |
| `composer test` | 41 passed, 1 skipped, 275 assertions; skip hanya tes GitHub nyata yang opt-in |
| PHPUnit dengan `DB_CONNECTION=pgsql` pada PostgreSQL 17 | 41 passed, 1 skipped, 275 assertions |
| `RUN_GITHUB_LIVE_TEST=1 php artisan test --filter=GitHubLiveTest` | 1 passed, 4 assertions; GET nyata ke API repositori publik `laravel/laravel` |
| `vendor/bin/pint --test` | PASS; format juga diterapkan dengan Pint |
| `npm run build` | PASS; font IBM Plex disajikan dari build lokal |
| `composer validate --strict` | PASS |
| `composer audit` | Tidak ada advisory keamanan |
| `npm audit --audit-level=high` | 0 vulnerabilities |
| `php artisan route:list` | PASS; route inti tercatat, local storage serving dinonaktifkan |
| `php artisan migrate:status` | Semua lima migration tercatat Ran pada database pengembangan SQLite; belum pada Neon |
| Playwright | 4 passed; workflow penuh 390, 768, 1440 piksel dan pemeriksaan CSRF/OAuth state |
| Docker production build | Berhasil dengan PHP-FPM/nginx, dependency produksi, dan aset Vite |
| Smoke container mode production | Login 200, `/up` 200, `/projects` 302 ke login; cookie secure/HttpOnly/Lax; no-store dan header keamanan teramati |
| Koneksi Neon dari workspace | Gagal: TCP 5432 connection refused/network unreachable. Tidak ada migration diterapkan ke Neon |
| OAuth GitHub asli dengan client produksi | Belum dijalankan: client ID/secret produksi belum tersedia |
| Deployment publik | Belum dilakukan; tidak ada URL produksi terverifikasi |

Tes PostgreSQL memakai container `handoff-postgres` dan database khusus `handoff_test`, tanpa koneksi ke database proyek lain. Smoke container menggunakan PostgreSQL lokal dengan TLS dinonaktifkan khusus jaringan QA; hasil ini bukan verifikasi TLS Neon atau kesiapan produksi publik. `handoff:production-check` pada konfigurasi lokal secara benar menolak environment/URL/OAuth/TLS yang belum memenuhi produksi. Tes konfigurasi produksi yang memenuhi seluruh gerbang juga lulus dengan fixture konfigurasi.

## Cakupan fungsi dan keamanan

- Guest ditolak pada halaman/data/backup/handoff privat. Allowlist kosong, akun tidak terdaftar, username sama dengan ID berbeda, dan sesi owner yang dicabut ditolak.
- OAuth state nyata ditolak jika salah; login callback pemilik, regenerasi/invalidation session melalui alur aplikasi, logout, pembatasan login, dan pembatalan OAuth diuji. Tidak ada token OAuth yang disimpan.
- CRUD proyek, mass-assignment protection, validasi URL/enum/ukuran/tag, state formulir invalid, pencarian case-insensitive, filter, sort, soft delete dengan konfirmasi nama, arsip, dan pemulihan diuji.
- Akses view/edit/delete/sync/checkpoint/handoff proyek pemilik lain ditolak. Pemilihan checkpoint proyek lain dan manipulasi state Livewire ditolak.
- Checkpoint append-only, kronologi, hasil handoff deterministik, data kosong, provenance manual/API, failed check yang tetap failure, dan sanitasi HTML/unsafe Markdown links diuji.
- API 401/403/404/429/500, timeout, izin parsial, snapshot lama, validasi host, serta permintaan read-only diuji dengan fake HTTP; satu pengujian API terpisah memakai GitHub nyata.
- Ekspor/impor via browser, version/size/schema validation, UUID duplikat termasuk variasi kapital, impor idempotent, checkpoint lama tidak ditimpa, timestamp dan instant lintas zona waktu, backup kosong, dan konfirmasi preview diuji.
- CSRF request tanpa token mendapat HTTP 419 dalam browser harness; unduhan privat tanpa session redirect ke login. Tidak ada error JavaScript pada tiga workflow utama. Tidak ada overflow horizontal pada viewport yang diuji.

## Browser, fixture, dan bukti

Alur tiap viewport: login Socialite ber-state → tambah proyek → reload persistence → edit → sync GitHub error → dua checkpoint → pilih historis → buat ulang → clipboard benar → download Markdown → pencarian/filter → export JSON → preview/import ulang tanpa checkpoint ganda → arsip → salah konfirmasi hapus ditolak → soft delete → pulihkan → dashboard → logout → akses privat ditolak.

Respons token/identitas GitHub dimock di `tests/Browser/server.php`; redirect state tetap melewati Socialite dan session database. Browser tidak menguji akun GitHub asli atau izin PAT privat. Sinkronisasi pada harness memakai respons 404 terkontrol. Entry point produksi tetap `public/index.php` dan tidak memuat harness. Fixture `Handoff Hub QA 390`, `Handoff Hub QA 768`, `Handoff Hub QA 1440` serta dua checkpoint masing-masing hanya ada pada SQLite browser khusus. Fixture tidak di-seed ke produksi.

Screenshot lokal: `docs/screenshots/{login,dashboard,projects,project,backup}-{390,768,1440}.png`. Screenshot dan hasil Playwright tidak dikomit; workflow Actions mengunggahnya sebagai artifact `browser-qa` dengan retensi 7 hari. Jangan memakai screenshot sebagai bukti aplikasi publik sudah live.

## Taste Skill

Instalasi berhasil melalui `npx skills add Leonxlnx/taste-skill -a codex --skill design-taste-frontend -y`, setelah membaca instruksi dan script upstream. Lokasi lokal `.agents/skills/design-taste-frontend/SKILL.md`. Upstream commit `12af471f627f866411b4bd785b19646d3672784c`; hash skill tercatat dalam `skills-lock.json`.

Arah `framed-sheet`, seed `421114848`, dipilih dari tiga opsi untuk sesi kerja developer: bingkai abu kehijauan, lembar putih, IBM Plex Sans, IBM Plex Mono khusus SHA/source, warna teal terbatas, tanpa gradient atau animasi masuk. Identitas produk: monogram dua jalur handoff dan hubungan proyek → checkpoint → handoff. Angka selalu dihitung dari record. Motion hanya feedback kontrol dan drawer, menghormati reduced motion.

Pertanyaan layar: dashboard “proyek mana dilanjutkan?”, daftar “proyek mana dicari?”, detail “di titik mana pekerjaan berhenti?”, checkpoint “apa bukti dan langkah berikutnya?”, backup “apa yang akan dipulihkan?”. Action utama tersedia pada title atau blok terkait.

`lint.mjs --app` menghasilkan 0 FAIL, 1 WARN; shadow yang ditandai hanya scrim drawer navigasi mobile, bukan kartu. `inspect.mjs --app` dijalankan pada login aktual dan HTML hasil render browser terautentikasi untuk enam halaman lain, memakai aset production-build lokal dengan font pada origin yang sama. Semua tujuh layar pada 1440/768/390 menghasilkan **0 FAIL**. Detail ringkas tersimpan di `docs/qa/taste-summary.json`.

| Layar | FAIL | WARN akhir |
| --- | --- | --- |
| Login | 0 | 1, unused-canvas: layout autentikasi memang kolom terpusat dengan margin seimbang, bukan sidebar aplikasi |
| Dashboard | 0 | 2, tiny-text dan no-data-hue |
| Daftar proyek | 0 | 1, tiny-text |
| Detail/handoff | 0 | 1, no-data-hue |
| Form proyek | 0 | 0 |
| Form checkpoint | 0 | 0 |
| Backup | 0 | 0 |

Pembelaan WARN: teks 11–12 px digunakan untuk tanggal, SHA, tag, caption, dan metadata; isi utama 13–14 px dan target sentuh mobile 44 px. Timeline berbasis teks memiliki titik teal kecil; tidak ada chart numerik yang perlu diberi warna, sehingga tidak ditambahkan grafik atau status berwarna palsu untuk mengejar meter. Font-fallback pada pengambilan statis pertama diperbaiki dengan penyajian aset pada origin yang sama; tidak ada WARN font pada inspeksi terakhir.

Seluruh fold dan contact sheet screenshot tiap viewport diperiksa. Perbaikan hasil kritik: filter mobile dilipat agar baris pertama lebih cepat terlihat; overflow filter desktop/tablet diperbaiki dengan grid; daftar dashboard diseimbangkan dan dibatasi tiga record; heading preview ditempatkan di bawah heading halaman; sumber Markdown tetap tersedia dan konfirmasi copy bisa dibaca screen reader. Kritik akhir: identitas dan grid konsisten, hierarki proyek/timeline/handoff jelas, nol/missing state jujur, nav melipat pada mobile, form masih panjang karena seluruh konteks checkpoint wajib tersedia. Nilai visual internal **7.5/10**, bukan penilaian pihak ketiga. Tidak ada gambar stok, lisensi foto, angka vanity, atau data contoh produksi yang perlu diganti.

## Temuan yang diselesaikan

Mock HTTP antar-skenario dipisahkan; font remote scaffold diganti font lokal; heading H1 ganda pada preview diperbaiki; timing inisialisasi Livewire diperhitungkan dalam tes browser; overflow filter diperbaiki; timestamp backup dinormalisasi; UUID dinormalisasi; rendering input invalid berupa array diamankan. Docker QA menemukan header keamanan ganda dari nginx; template image disesuaikan agar header respons dinamis dimiliki Laravel dan aturan blok file nginx tetap berlaku.

## Batas delivery

CI aktual dan status merge harus dibaca dari checks PR release. Hasil lokal tidak menggantikan checks remote. Produksi menunggu hosting berizin, persetujuan biaya jika berbayar, credential OAuth, koneksi Neon dari host, serta verifikasi callback/authorization/persistence melalui URL publik. Repository awal berstatus publik dan perubahan visibility ditolak HTTP 403; pemilik perlu mengubahnya lewat GitHub Settings jika ingin repository private. Status delivery aplikasi belum dapat disebut produksi live.
