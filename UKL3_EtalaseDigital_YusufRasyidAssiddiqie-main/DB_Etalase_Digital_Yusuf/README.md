# Etalase Digital Stella

Aplikasi etalase karya siswa dengan PHP, PDO, MySQL, dan Bootstrap.

## Instalasi (XAMPP)
1. Letakkan folder proyek di dalam `C:\xampp\htdocs`.
2. Nyalakan Apache dan MySQL dari XAMPP.
3. Impor `DB_Etalase_Digital_Yusuf/database.sql` melalui phpMyAdmin. File ini membuat database `DB_Etalase_Digital_Yusuf`.
4. Pastikan host, username, password MySQL, dan nama database di `koneksi.php` sesuai konfigurasi XAMPP.
5. Buka folder proyek melalui `http://localhost/UKL3_EtalaseDigital_YusufRasyidAssiddiqie-main/`. Jika nama folder proyek berbeda, ganti bagian setelah `localhost/` dengan nama folder tersebut. Halaman akan mengarahkan ke form login.

Jika database lama sudah pernah dibuat dengan struktur berbeda, cadangkan dahulu data yang diperlukan. Setelah itu gunakan database kosong sebelum mengimpor skema baru; `CREATE TABLE IF NOT EXISTS` tidak mengubah tabel lama yang strukturnya berbeda.

## Akun contoh
Semua akun contoh menggunakan password awal `password123`; segera ganti setelah login pertama.

| Username | Role |
|---|---|
| `admin` | admin |
| `yusufrasyid` | siswa |

Data siswa contoh memiliki NIS `NIS-000001` dan kelas `XI RPL 1`.

## Skema
- `users`: username unik untuk login, password hash, dan role `admin` atau `siswa`.
- `students`: profil siswa yang terhubung unik ke akun user; foto profil boleh kosong.
- `karya`: karya siswa dengan status `draft` atau `published`.

Pendaftaran publik membuat akun siswa dan profil siswa sekaligus. Siswa mengelola karya miliknya, sedangkan admin dapat mengelola akun siswa dan seluruh karya.
