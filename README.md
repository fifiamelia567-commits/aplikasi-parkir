# Aplikasi Parkir

Aplikasi Parkir adalah aplikasi berbasis web yang dibangun menggunakan PHP Native dan MySQL untuk membantu pengelolaan kendaraan, transaksi parkir, tarif, area parkir, serta pengguna berdasarkan hak akses.

## Fitur Aplikasi

- Login dan autentikasi pengguna.
- Hak akses berdasarkan role: Admin, Petugas, dan Owner.
- Pengelolaan data pengguna.
- Pengelolaan data kendaraan.
- Pengelolaan area parkir dan kapasitas.
- Pengaturan tarif berdasarkan jenis kendaraan.
- Pencatatan transaksi kendaraan masuk dan keluar.
- Perhitungan durasi dan biaya parkir melalui proses transaksi.
- Riwayat aktivitas pengguna.
- Laporan transaksi parkir dan pendapatan, jika modul laporan tersedia.

> Catatan: Fitur yang disebutkan mengikuti struktur database dan rancangan aplikasi. Fitur yang belum dibuat di antarmuka perlu dikembangkan terlebih dahulu.

## Teknologi

- PHP Native
- MySQL / MariaDB
- HTML
- CSS
- JavaScript
- Apache melalui XAMPP

## Role Pengguna

| Role | Hak akses |
|---|---|
| Admin | Mengelola pengguna, kendaraan, area parkir, tarif, transaksi, dan melihat data sesuai modul yang tersedia. |
| Petugas | Mencatat kendaraan masuk dan keluar serta menangani transaksi parkir sesuai hak akses. |
| Owner | Memantau data parkir dan melihat laporan sesuai modul yang tersedia. |

## Struktur Database

Database yang digunakan: `db_parkir`

| Tabel | Keterangan |
|---|---|
| `tb_user` | Data pengguna, username, password, role, dan status aktif. |
| `tb_kendaraan` | Data kendaraan, nomor polisi, jenis, warna, pemilik, dan pengguna terkait. |
| `tb_area_parkir` | Data area parkir, kapasitas, dan jumlah terisi. |
| `tb_tarif` | Tarif parkir berdasarkan jenis kendaraan. |
| `tb_transaksi` | Data kendaraan masuk/keluar, waktu, durasi, biaya, petugas, dan area. |
| `tb_log_aktivitas` | Catatan aktivitas pengguna. |

## Persyaratan

Pastikan perangkat telah memiliki:
- XAMPP atau web server yang mendukung PHP.
- MySQL atau MariaDB.
- Browser web.

## Cara Instalasi

1. Salin folder proyek ke direktori web server, misalnya:

   `C:\xampp\htdocs\aplikasi-parkir`

2. Jalankan **Apache** dan **MySQL** melalui XAMPP.

3. Buka phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Buat database bernama `db_parkir`.

5. Impor file database `db_parkir.sql` melalui menu **Import** di phpMyAdmin.

6. Buka file konfigurasi koneksi database pada proyek, lalu sesuaikan host, nama database, username, dan password MySQL dengan pengaturan lokal.

   Contoh konfigurasi umum XAMPP:
   - Host: `127.0.0.1`
   - Database: `db_parkir`
   - Username: `root`
   - Password: kosong (jika masih menggunakan konfigurasi bawaan XAMPP)

7. Buka aplikasi melalui browser:

   `http://localhost/aplikasi-parkir`

## Struktur Tabel Transaksi

Tabel `tb_transaksi` menyimpan informasi utama transaksi parkir, termasuk:
- Kendaraan yang parkir.
- Waktu masuk dan waktu keluar.
- Tarif yang digunakan.
- Durasi parkir.
- Total biaya.
- Status kendaraan (`masuk` atau `keluar`).
- Pengguna/petugas dan area parkir.

## Keamanan

- Gunakan password yang disimpan dengan metode hashing yang sesuai dengan kode autentikasi aplikasi.
- Validasi input pengguna dan gunakan prepared statements untuk query database.
- Batasi halaman dan tindakan berdasarkan role pengguna.
- Jangan unggah kredensial database atau data sensitif ke repositori publik.
- Nonaktifkan atau hapus file pengujian setelah digunakan.

## Pengembangan Selanjutnya

Fitur yang dapat ditambahkan:
- Dashboard statistik dan grafik.
- Pencarian transaksi berdasarkan plat nomor.
- Cetak tiket dan struk parkir.
- Ekspor laporan ke PDF atau Excel.
- Backup dan restore database.
- QR code untuk tiket parkir.

## Pengembang

Proyek pembelajaran aplikasi parkir berbasis web menggunakan PHP Native dan MySQL.
