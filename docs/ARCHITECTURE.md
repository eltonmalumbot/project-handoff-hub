# Arsitektur

## Alur aplikasi

Browser → route Laravel/session/CSRF → middleware `auth` dan `owner` → Form Request/Policy → Eloquent → database. Blade merender halaman; Livewire menangani pencarian/filter/paginasi dan pilihan checkpoint pada handoff. JavaScript lokal menangani drawer navigasi, filter mobile, clipboard, dan feedback loading sinkronisasi.

`User` memakai `github_id` unik, login GitHub, nama, dan session. Tidak ada password login, email linking, atau token OAuth yang disimpan. `Project` dimiliki User; `Checkpoint` dimiliki Project. FK melindungi relasi, indeks mencakup pemilik/status/update dan proyek/waktu checkpoint. UUID backup unik per pemilik/proyek. Proyek memakai soft delete; checkpoint dipertahankan di sampah. Arsip adalah status terpisah. Pemulihan dari sampah memeriksa policy.

Checkpoint immutable melalui UI dan route aplikasi: koreksi dilakukan dengan membuat checkpoint baru. Urutan berdasarkan `occurred_at DESC, id DESC`; judul dan branch tidak dijadikan identitas. Tanggal input tanpa offset ditafsirkan Asia/Jakarta. Offset pada impor dinormalisasi ke Asia/Jakarta agar instant dan timestamp tetap sama setelah roundtrip SQLite/PostgreSQL.

## Service

- `HandoffGeneratorService`: mengotorisasi proyek/checkpoint, memastikan keduanya berhubungan, menghasilkan Markdown deterministik dari record tersimpan. Tidak memanggil AI atau mengeksekusi tes. Teks manual tidak disertifikasi; data kosong ditandai tidak tersedia. Snapshot API terbaru dipisahkan dan tidak dianggap bukti historis checkpoint.
- `GitHubRepositoryService`: hanya HTTPS ke host API GitHub yang ditetapkan kode; URL repo divalidasi, path/commit dienkode, redirect tidak diikuti, timeout diberlakukan. GET metadata/default branch/commit/PR/status/checks. Tidak ada endpoint mutasi repositori. PR maksimal 30 dan checks maksimal 100 per sinkronisasi; batas ini ditampilkan dalam handoff. Status commit dan check run ditampilkan terpisah tanpa mengarang agregat “lulus”.
- `BackupService`: ekspor whitelist field proyek/checkpoint termasuk UUID dan timestamp; tidak mengekspor user, session, konfigurasi, token, atau snapshot API. Impor versi 1, maksimal 2 MiB, 100 proyek, 500 checkpoint per proyek, 5000 total. Validasi URL, enum, panjang, tanggal, UUID, struktur, dan field tak dikenal. UUID dinormalisasi huruf kecil. Pratinjau disimpan server-side di cache database selama 15 menit dan diikat user/session dengan nonce acak. Transaksi dan lock User menyerialkan impor; proyek lama dan UUID checkpoint lama tidak ditimpa, checkpoint baru ditambahkan. Status sampah proyek lama tidak diubah. Proyek baru memulihkan status sampah dari backup.

Penggantian URL repositori menghapus snapshot lama agar bukti repo sebelumnya tidak melekat pada repo baru. Error metadata atau timeout mempertahankan snapshot sebelumnya. Izin parsial menghasilkan peringatan. Snapshot bertimestamp tetap bisa kedaluwarsa.

## Batas sistem

Satu aplikasi Laravel, tanpa API publik terpisah, queue worker wajib, AI service, atau penulisan ke repositori sumber. Session dan cache database sesuai deployment beberapa instance. File impor dibaca dari temporary upload, lalu data tervalidasi disimpan ke cache database; tidak ada file unggahan jangka panjang. Handoff dan JSON di-stream saat diunduh. Aset dan font disajikan lokal dari hasil build Vite.

`handoff:production-check --database` memeriksa konfigurasi, koneksi, dan migrasi tanpa mencetak credential atau menulis data. `handoff:browser-setup` hanya menyiapkan SQLite browser pada environment testing dan path khusus yang tepat. `tests/Browser/server.php` merupakan test harness, bukan entry point deploy.

## Infrastruktur

Neon project khusus `summer-mode-61001274`, nama `project-handoff-hub`, region `aws-ap-southeast-1`, PostgreSQL 17, organisasi Free. Proyek ini terpisah dari semua aplikasi lain. Schema belum diterapkan di Neon karena TCP 5432 dari workspace tidak tersedia. Schema dan alur aplikasi diuji dengan PostgreSQL 17 lokal terisolasi. Gunakan host direct untuk migrasi, host pooled untuk runtime; simpan credential di secret manager host.
