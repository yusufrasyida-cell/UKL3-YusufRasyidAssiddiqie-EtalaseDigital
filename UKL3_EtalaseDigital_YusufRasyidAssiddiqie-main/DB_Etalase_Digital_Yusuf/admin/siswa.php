<?php
require_once __DIR__ . '/../koneksi.php';
require_once '../middleware/auth_check.php';
require_once '../includes/helpers.php';

// Admin & Super Admin boleh mengelola akun siswa
check_role(['admin'], '../');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $nis      = trim($_POST['nis'] ?? '');
        $kelas    = trim($_POST['kelas_jurusan'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $nama === '' || $nis === '' || $kelas === '' || $password === '') {
            $error = "Semua kolom wajib diisi!";
        } elseif (mb_strlen($username, 'UTF-8') > 50 || mb_strlen($nama, 'UTF-8') > 100
            || mb_strlen($nis, 'UTF-8') > 30 || mb_strlen($kelas, 'UTF-8') > 50) {
            $error = "Panjang username, NIS, nama, atau kelas melebihi batas.";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter!";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :pass, 'siswa')");
                $stmt->execute([
                    ':username' => $username,
                    ':pass'  => password_hash($password, PASSWORD_DEFAULT),
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
                set_flash('success', 'Akun siswa berhasil ditambahkan.');
                header('Location: siswa.php');
                exit();
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = is_duplicate_error($ex) ? "Username atau NIS sudah terdaftar!" : "Gagal menyimpan data siswa.";
            }
        }
    } elseif ($action === 'reset') {
        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id_user = :id AND role = 'siswa'");
        $stmt->execute([
            ':pass' => password_hash(DEFAULT_RESET_PASSWORD, PASSWORD_DEFAULT),
            ':id'   => (int) ($_POST['id'] ?? 0),
        ]);
        set_flash('success', 'Password siswa direset menjadi ' . DEFAULT_RESET_PASSWORD . '.');
        header('Location: siswa.php');
        exit();
    } elseif ($action === 'hapus') {
        if (delete_user_with_files($pdo, (int) ($_POST['id'] ?? 0), ['siswa'])) {
            set_flash('success', 'Akun siswa beserta karyanya berhasil dihapus.');
        }
        header('Location: siswa.php');
        exit();
    }
}

$siswa = $pdo->query("SELECT users.id_user, users.username, students.nama_lengkap, students.nis, students.kelas_jurusan
                      FROM users
                      JOIN students ON students.id_user = users.id_user
                      WHERE users.role = 'siswa'
                      ORDER BY students.id_siswa DESC")->fetchAll();

$base   = '../';
$active = 'siswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - Etalase Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/kelola.css">
</head>
<body>

<?php require __DIR__ . '/../includes/navbar.php'; ?>

<div class="container my-4">
    <?= render_flash() ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-custom p-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-plus-fill text-primary"></i> Tambah Siswa</h4>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label">Username untuk Login</label>
                        <input type="text" name="username" maxlength="50" class="form-control" required placeholder="Username" value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" maxlength="100" class="form-control" required placeholder="Nama Siswa" value="<?= e($_POST['nama_lengkap'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" maxlength="30" class="form-control" required placeholder="NIS siswa" value="<?= e($_POST['nis'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas/Jurusan</label>
                        <input type="text" name="kelas_jurusan" maxlength="50" class="form-control" required placeholder="Contoh: XI RPL 1" value="<?= e($_POST['kelas_jurusan'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-check-fill"></i> Simpan Data Siswa</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-custom p-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-people-fill text-dark"></i> Daftar Akun Siswa</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>NIS</th>
                                <th>Kelas/Jurusan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswa) === 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada akun siswa.</td></tr>
                            <?php endif; ?>
                            <?php $no = 1; foreach ($siswa as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= e($row['username']) ?></code></td>
                                <td><b><?= e($row['nama_lengkap']) ?></b></td>
                                <td><?= e($row['nis']) ?></td>
                                <td><?= e($row['kelas_jurusan']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST" onsubmit="return confirm('Reset password siswa ini menjadi <?= e(DEFAULT_RESET_PASSWORD) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reset">
                                            <input type="hidden" name="id" value="<?= (int) $row['id_user'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-key"></i> Reset Password</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Hapus akun siswa ini beserta karya yang diunggah?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $row['id_user'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
