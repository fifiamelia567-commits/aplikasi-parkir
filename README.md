# Aplikasi Parkir - PHP Native

## Instalasi
1. Ekstrak folder `aplikasi-parkir` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka phpMyAdmin dan impor file SQL database parkir yang diberikan.
4. Pastikan nama database `db_parkir`. Jika berbeda, ubah `config/koneksi.php`.
5. Buka `http://localhost/aplikasi-parkir/`.

## Role
- Admin: dashboard, user, tarif, area, kendaraan, log aktivitas.
- Petugas: kendaraan masuk, transaksi keluar, cetak struk.
- Owner: dashboard dan rekap transaksi berdasarkan tanggal.

## Catatan login
Login mendukung hash SHA-256 pada database awal dan hash `password_hash()` untuk user baru/perubahan password. Akun hanya dapat membuka halaman sesuai rolenya: `admin1` untuk admin, `petugas1` untuk petugas, dan `owner1` untuk owner. Pesan “Akses ditolak” berarti halaman yang dibuka tidak sesuai dengan role akun.

Password awal tidak disimpan sebagai teks biasa. Jika tidak diketahui, buat hash password baru di terminal:

```powershell
php -r "echo password_hash('GANTI_DENGAN_PASSWORD_BARU', PASSWORD_DEFAULT);"
```

Salin hasilnya, lalu jalankan di tab SQL phpMyAdmin untuk akun yang ingin dipulihkan:

```sql
UPDATE tb_user
SET password = 'TEMPEL_HASH_HASIL_PERINTAH_DI_SINI'
WHERE username = 'admin1';
```

Ganti `admin1` dengan `petugas1` atau `owner1` bila diperlukan, lalu login menggunakan password baru yang dipilih. Jangan gunakan password contoh untuk lingkungan publik.

## Keamanan dan batasan
- Gunakan prepared statements PDO.
- Batasi akses berdasarkan role.
- Untuk produksi, tambahkan token CSRF pada semua form, audit transaksi, dan konfigurasi kredensial database non-root.
- Penghitungan durasi dibulatkan ke atas per jam dengan minimum 1 jam.
