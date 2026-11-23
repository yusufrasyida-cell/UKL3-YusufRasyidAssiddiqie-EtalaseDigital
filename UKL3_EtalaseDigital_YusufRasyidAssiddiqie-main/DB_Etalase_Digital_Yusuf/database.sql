CREATE DATABASE IF NOT EXISTS DB_Etalase_Digital_Yusuf
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DB_Etalase_Digital_Yusuf;

CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa'
);

CREATE TABLE IF NOT EXISTS students (
    id_siswa INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    nis VARCHAR(30) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    kelas_jurusan VARCHAR(50) NOT NULL,
    foto_profil VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_students_user
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS karya (
    id_karya INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    kategori VARCHAR(50),
    gambar VARCHAR(255) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    tanggal_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_karya_student
        FOREIGN KEY (id_siswa) REFERENCES students(id_siswa) ON DELETE CASCADE
);

-- Akun contoh (password: password123). Ganti setelah login pertama.
INSERT IGNORE INTO users (username, password, role) VALUES
('admin', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'admin'),
('yusufrasyid', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'siswa');

INSERT IGNORE INTO students (id_user, nis, nama_lengkap, kelas_jurusan, foto_profil)
SELECT id_user, 'NIS-000001', 'yusuf rasyid assiddiqie', 'XI RPL 1', NULL
FROM users
WHERE username = 'yusuf rasyid assiddiqie' AND role = 'siswa';
