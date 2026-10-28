# Sistem Servis Motor — persiapan migrasi Supabase

## Status

Paket ini mengubah koneksi PHP menjadi PDO PostgreSQL dan menyiapkan skema Supabase. **Belum boleh dianggap migrasi produksi yang sudah tervalidasi**: belum ada project Supabase untuk menjalankan pengujian koneksi, dan query/fitur perlu diuji dengan data nyata setelah skema dibuat.

## Langkah

1. Buat project Supabase baru. Jangan jalankan skema ini pada database berisi data tanpa backup.
2. Buka SQL Editor dan jalankan `supabase_schema.sql`.
3. Atur environment variable di hosting: `SUPABASE_DB_HOST`, `SUPABASE_DB_PORT` (5432), `SUPABASE_DB_NAME` (postgres), `SUPABASE_DB_USER`, `SUPABASE_DB_PASSWORD`, dan `FONNTE_TOKEN`.
4. Gunakan host dan kredensial dari menu Connect Supabase. Pada hosting yang tidak mendukung IPv6, coba connection pooler Supabase dan ikuti host/port/user yang ditampilkan dashboard.
5. Pastikan PHP mempunyai ekstensi `pdo_pgsql`, `mbstring`, `curl`, dan `fileinfo`.
6. Migrasikan data MySQL setelah mencocokkan kedua dump SQL. Dump `db_servis_montor (1).sql` lebih baru dan memiliki kolom `servis.id_pelanggan`; jangan mengimpor dua dump sekaligus.
7. Setelah import data dengan ID eksplisit, sinkronkan sequence identity untuk tabel yang diimpor agar insert baru tidak bentrok.
8. Uji semua alur: login admin/petugas, login/daftar pelanggan, ingat saya, CRUD pelanggan/servis, riwayat servis, laporan, notifikasi Fonnte, foto profil, logout, dan akses seluler.

## Catatan kompatibilitas yang harus diuji

- Query MySQL yang memakai fungsi spesifik, DDL saat request, atau asumsi tipe data harus diperiksa. Beberapa perubahan awal sudah diterapkan, tetapi belum dapat diklaim semua query telah kompatibel.
- `remember_tokens` sengaja tidak memiliki foreign key ke `users` karena tabel ini dipakai di alur login dan mungkin ada token lama; validasi pengguna dilakukan saat pemulihan token.
- File unggahan foto disimpan lokal pada proyek. Pada hosting ephemeral, pindahkan unggahan ke Supabase Storage atau layanan penyimpanan persisten.
- Jangan menggunakan `service_role` key pada browser. Aplikasi PHP memakai kredensial database server-side melalui environment variable.
- Token Fonnte yang pernah ada di arsip lama harus dirotasi. Gunakan token baru hanya sebagai environment variable; jangan masukkan ke Git atau ZIP.

## Audit duplikat dan file yang tidak dihapus

- `dashboard.php` dan `dashboard2.php`: mirip ukurannya tetapi belum terbukti identik atau salah satunya tidak dipakai. Keduanya dipertahankan sampai semua tautan/include diperiksa.
- `db_servis_montor.sql` dan `db_servis_montor (1).sql`: dua dump dengan skema berbeda. Jangan hapus sumber sebelum migrasi data diverifikasi; gunakan dump terbaru sebagai acuan awal.
- `migrasi_pelanggan.sql`, `pelanggan/migrasi.sql`, dan `migrasi_fitur_v3.sql`: file migrasi historis; tidak dijalankan otomatis. Tinjau perubahan yang sudah tercakup sebelum menerapkannya.
- `buat_password.php` dan `buat_password_admin.php`: cek apakah masih diperlukan; jangan hapus sebelum pemeriksaan rute selesai.
- `includes/header.php` dan `includes/footer.php` kosong pada arsip ini; pertahankan sementara karena mungkin sengaja menjadi placeholder/include.

## Hosting

Gunakan hosting PHP yang menyediakan `pdo_pgsql` dan environment variables. Untuk opsi Docker, file `Dockerfile` disediakan sebagai dasar; paket gratis Render dapat tidur saat tidak digunakan dan filesystem lokal tidak persisten. Foto profil perlu dipindahkan ke storage persisten sebelum produksi.


## Pengujian yang dilakukan pada paket

- Semua file PHP yang tersedia lulus pemeriksaan sintaks `php -l` pada lingkungan pembuat paket.
- Lingkungan pembuat paket tidak memiliki ekstensi `pdo_pgsql`, dan belum tersambung ke project Supabase pengguna; karena itu uji koneksi/database dan uji alur aplikasi belum dapat dilakukan.
- `dashboard_user.php` merujuk `includes/fitur.php`, tetapi file tersebut tidak ada di arsip sumber. Jangan menganggap halaman itu berfungsi sebelum dependensi ini dipulihkan atau rujukannya diperbaiki.
- `dashboard.php` dan `dashboard2.php` memiliki isi berbeda (hash berbeda) dan belum ada rujukan langsung ke `dashboard2.php`; keduanya dipertahankan untuk menghindari penghapusan fitur secara keliru.

## Perbaikan tambahan pada paket

- Menambahkan `foto_profil` pada definisi tabel `pelanggan` dan `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` agar cocok dengan halaman profil pelanggan.
- Menambahkan `includes/fitur.php` yang sebelumnya dirujuk oleh `dashboard_user.php` tetapi tidak tersedia di arsip.
- Pemeriksaan sintaks PHP dilakukan ulang setelah perubahan. Koneksi database langsung dan seluruh alur tetap perlu diuji pada hosting yang sudah diberi kredensial Supabase.
