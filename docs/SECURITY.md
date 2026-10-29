# Keamanan

## Batas akses

OAuth GitHub Socialite tetap stateful; tidak ada `stateless()`, login demo, atau registrasi publik. `setScopes([])` meminta identitas dasar tanpa izin `repo` atau email tambahan. Callback memeriksa ID numerik sebelum menulis User atau membuat session. Kesamaan username tidak memberi akses. Session diregenerasi saat login dan diinvalidasi saat logout, termasuk regenerasi token CSRF. Middleware memeriksa ulang allowlist pada setiap request sehingga sesi akun yang dicabut tidak tetap berhak mengakses aplikasi.

Policy mengikat Project pada `user_id`; checkpoint dicek melalui proyek pemilik. Download memakai scoped binding dan pemeriksaan pasangan proyek/checkpoint. ID project Livewire dikunci; render/action tetap mengotorisasi record dan pemilik. Persistent middleware memeriksa sesi pada update Livewire. Mass assignment hanya mengizinkan field bisnis; `user_id`, UUID, dan snapshot GitHub tidak diterima dari formulir.

Semua mutasi memakai POST/PUT/DELETE dan CSRF. Login dibatasi 10 request/menit/IP; sinkronisasi 3/menit/user; backup 10/menit. Penghapusan perlu nama proyek yang persis sama, kemudian soft delete. Checkpoint lama tetap tersimpan. Backup memerlukan login, allowlist, validasi, pratinjau, dan checkbox konfirmasi; data impor tidak dapat memilih pemilik lain.

## Data dan output

Blade meng-escape field. Preview Markdown memakai CommonMark dengan HTML disaring dan unsafe links dinonaktifkan. URL deployment dibatasi HTTP/HTTPS, repo/PR hanya host GitHub HTTPS. Sinkronisasi memakai host API tetap dan tidak mengikuti redirect; token baca tidak dikirim ke host lain. Tidak ada aksi write GitHub di aplikasi. Respons gagal API tidak ditampilkan bersama token/header sensitif.

OAuth token hanya berada dalam objek request Socialite dan tidak disimpan. Jika perlu repo privat, gunakan `GITHUB_READ_TOKEN` fine-grained terpisah: selected repositories, Metadata read, Contents read, Pull requests read, Commit statuses read, Checks read. Tidak diperlukan izin write. Pastikan kebijakan organisasi mengizinkannya; aplikasi tidak dapat membuktikan izin token hanya dari string token.

`.env*`, credential, SQLite, session, log, hasil browser, dan snapshot HTML QA tidak dikomit. Backup tidak membawa credential sistem, tetapi notes/instruksi dapat berisi informasi sensitif yang dimasukkan manual; pemilik bertanggung jawab tidak menempelkan rahasia ke catatan. Simpan backup dengan kontrol akses yang sesuai.

## Produksi

Wajib HTTPS, `APP_DEBUG=false`, APP_KEY acak, secure/HttpOnly/SameSite=Lax cookie, session database terenkripsi, PostgreSQL TLS `verify-full`, dan root CA yang valid. Provider memaksa URL HTTPS dan secure cookie pada environment production. Header nosniff, DENY framing, same-origin referrer, no-store/private dan pembatasan permission browser diterapkan; HSTS hanya production. Jangan memakai server pengembangan atau test router sebagai entry point deploy. Root web hanya direktori `public` dan hanya front controller `public/index.php` dieksekusi. Jangan mengekspos source, `.env`, `tests`, atau temporary snapshot QA.

Gunakan role migrasi terpisah dari role runtime. Runtime memerlukan SELECT/INSERT/UPDATE/DELETE tabel aplikasi dan akses sequence; tidak memerlukan DDL, superuser, atau akses database proyek lain. Atur secret di panel hosting, jangan di build arguments atau commit.

## Review dan risiko yang tersisa

Tes mencakup penolakan ID asing, allowlist kosong/dicabut, lintas pemilik, manipulasi checkpoint historis, input unsafe, invalid OAuth state, CSRF melalui browser, konfirmasi delete, deduplikasi backup, dan sanitasi Markdown. Audit dependency harus diperiksa lagi pada setiap rilis. Tidak dilakukan audit penetrasi eksternal.

Belum ada bukti login OAuth nyata dengan client produksi atau deployment publik. Hosting dan koneksi Neon dari host produksi perlu diverifikasi. Snapshot GitHub adalah bukti API bertimestamp, bukan jaminan kondisi terkini atau keberhasilan tes/deployment. Repo GitHub saat pengerjaan berstatus publik dan perubahan visibilitas ditolak integrasi HTTP 403; pemilik perlu mengubah ke private melalui GitHub Settings jika kode harus privat. Aplikasi tetap memerlukan autentikasi untuk data workspace.

Jika APP_KEY bocor, rotasi melalui prosedur host, invalidasi seluruh session, rotasi secret OAuth/PAT/database yang terdampak, lalu audit log akses. Rotasi APP_KEY membuat session terenkripsi lama tidak dapat digunakan.
