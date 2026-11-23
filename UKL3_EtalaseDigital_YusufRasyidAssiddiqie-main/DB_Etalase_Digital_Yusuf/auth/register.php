<?php
require_once __DIR__ . '/../koneksi.php';
require_once '../middleware/auth_check.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $nis      = trim($_POST['nis'] ?? '');
    $kelas    = trim($_POST['kelas_jurusan'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $nama === '' || $nis === '' || $kelas === '' || $password === '') {
        $error = "Semua kolom wajib diisi!";
    } elseif (mb_strlen($username, 'UTF-8') > 50 || mb_strlen($nama, 'UTF-8') > 100
        || mb_strlen($nis, 'UTF-8') > 30 || mb_strlen($kelas, 'UTF-8') > 50) {
        $error = "Panjang username, NIS, nama, atau kelas melebihi batas.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'siswa')");
            $stmt->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $student = $pdo->prepare("INSERT INTO students (id_user, nis, nama_lengkap, kelas_jurusan)
                                      VALUES (:id_user, :nis, :nama, :kelas)");
            $student->execute([
                ':id_user' => (int) $pdo->lastInsertId(),
                ':nis' => $nis,
                ':nama' => $nama,
                ':kelas' => $kelas,
            ]);
            $pdo->commit();
            $success = "Pendaftaran berhasil! Silakan login.";
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = is_duplicate_error($ex)
                ? "Username atau NIS sudah terdaftar."
                : "Terjadi kesalahan saat mendaftar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Etalase Digital Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="card card-login p-4 my-4">
    <div class="text-center mb-4">
        <img src="../assets/img/logo.png" alt="Logo Telkom Schools" style="max-height: 60px; width: auto;" class="mb-2">
        <h3 class="fw-bold mt-2">Registrasi Siswa</h3>
        <p class="text-muted small">Buat akun siswa untuk memamerkan karya Anda</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success py-2" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            <div class="mt-2">
                <a href="login.php" class="btn btn-sm btn-success w-100">Ke Halaman Login</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" maxlength="50" class="form-control" placeholder="Buat username untuk login" value="<?= e($_POST['username'] ?? '') ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                <input type="text" name="nama_lengkap" maxlength="100" class="form-control" placeholder="Masukkan nama lengkap" value="<?= e($_POST['nama_lengkap'] ?? '') ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">NIS</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-card-list"></i></span>
                <input type="text" name="nis" maxlength="30" class="form-control" placeholder="Masukkan NIS" value="<?= e($_POST['nis'] ?? '') ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Kelas/Jurusan</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-mortarboard"></i></span>
                <input type="text" name="kelas_jurusan" maxlength="50" class="form-control" placeholder="Contoh: XI RPL 1" value="<?= e($_POST['kelas_jurusan'] ?? '') ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Buat password (min. 6 karakter)" minlength="6" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Konfirmasi Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">Daftar Sekarang <i class="bi bi-arrow-right-short"></i></button>
    </form>
    <?php endif; ?>

    <div class="text-center pt-2 border-top">
        <span class="text-muted small">Sudah punya akun?</span>
        <a href="login.php" class="text-primary fw-semibold small text-decoration-none">Login di sini</a>
    </div>
</div>

</body>
</html>
