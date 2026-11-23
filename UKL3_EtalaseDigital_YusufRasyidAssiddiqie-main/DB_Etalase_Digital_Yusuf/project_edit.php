<?php
require_once __DIR__ . '/koneksi.php';
require_once 'middleware/auth_check.php';
require_once 'includes/helpers.php';

check_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM karya WHERE id_karya = :id");
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    die("Data karya tidak ditemukan.");
}

// Siswa hanya boleh mengedit karya miliknya; admin & super admin boleh semua
if (!is_staff() && (int) $project['id_siswa'] !== (int) $_SESSION['student_id']) {
    http_response_code(403);
    die("Akses Ditolak: Anda tidak dapat mengedit karya milik siswa lain.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nama_project = trim($_POST['nama_project'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $status       = $_POST['status'] ?? 'draft';
    $gambar_nama  = $project['gambar'];
    $gambar_baru  = null;

    if ($nama_project === '' || $deskripsi === '') {
        $error = "Judul dan deskripsi karya wajib diisi!";
    } elseif (mb_strlen($nama_project, 'UTF-8') > 150 || mb_strlen($kategori, 'UTF-8') > 50) {
        $error = "Judul maksimal 150 karakter dan kategori maksimal 50 karakter.";
    } elseif (!in_array($status, ['draft', 'published'], true)) {
        $error = "Status karya tidak valid.";
    } else {
        // Gambar baru bersifat opsional
        $adaFile = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($adaFile) {
            $gambar_baru = save_project_image($_FILES['gambar'], UPLOAD_DIR, $error);
            if ($gambar_baru !== null) {
                $gambar_nama = $gambar_baru;
            }
        }

        if ($error === '') {
            try {
                $upd = $pdo->prepare("UPDATE karya
                                      SET judul = :nama, deskripsi = :desk, kategori = :kategori,
                                          gambar = :img, status = :status
                                      WHERE id_karya = :id");
                $upd->execute([
                    ':nama' => $nama_project,
                    ':desk' => $deskripsi,
                    ':kategori' => $kategori !== '' ? $kategori : null,
                    ':img'  => $gambar_nama,
                    ':status' => $status,
                    ':id'   => $id,
                ]);

                if ($gambar_baru !== null) {
                    delete_project_image(UPLOAD_DIR, $project['gambar']); // hapus gambar lama
                }
                set_flash('success', 'Project berhasil diperbarui.');
                header('Location: index.php');
                exit();
            } catch (PDOException $ex) {
                if ($gambar_baru !== null) {
                    delete_project_image(UPLOAD_DIR, $gambar_baru);
                }
                $error = "Terjadi kesalahan sistem saat menyimpan perubahan.";
            }
        }
    }

    // Tampilkan kembali input terakhir jika gagal
    $project['judul'] = $nama_project;
    $project['deskripsi']    = $deskripsi;
    $project['kategori'] = $kategori;
    $project['status'] = $status;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card card-form p-4">
                <h3 class="fw-bold mb-4"><i class="bi bi-pencil-square text-warning"></i> Edit Project Karya</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Project</label>
                        <input type="text" name="nama_project" class="form-control" maxlength="150" value="<?= e($project['judul']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Project</label>
                        <textarea name="deskripsi" class="form-control" rows="4" required><?= e($project['deskripsi']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <input type="text" name="kategori" maxlength="50" class="form-control" value="<?= e($project['kategori'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Gambar Saat Ini</label>
                        <img src="uploads/<?= e($project['gambar']) ?>" class="img-thumbnail preview-img mb-2" alt="Gambar saat ini">
                        <input type="file" id="gambar" name="gambar" class="form-control" accept="image/png, image/jpeg, image/webp">
                        <small class="text-muted">* Biarkan kosong jika tidak ingin mengganti gambar (maks. 2MB).</small>
                        <div class="preview-box" id="previewBox">
                            <p class="small text-muted mb-1">Pratinjau Gambar Baru:</p>
                            <img id="imagePreview" src="" alt="Preview Gambar">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= $project['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $project['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save"></i> Update Project</button>
                        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('gambar').addEventListener('change', function () {
    var box = document.getElementById('previewBox');
    var img = document.getElementById('imagePreview');
    var file = this.files[0];
    if (!file) { img.removeAttribute('src'); box.style.display = 'none'; return; }
    var reader = new FileReader();
    reader.addEventListener('load', function () { img.src = reader.result; box.style.display = 'block'; });
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
