<?php
require_once __DIR__ . '/koneksi.php';
require_once 'middleware/auth_check.php';
require_once 'includes/helpers.php';

check_login();

// Penghapusan hanya lewat POST + token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM karya WHERE id_karya = :id");
    $stmt->execute([':id' => $id]);
    $project = $stmt->fetch();

    if ($project) {
        // Siswa hanya boleh menghapus karya miliknya; admin & super admin boleh semua
        if (!is_staff() && (int) $project['id_siswa'] !== (int) $_SESSION['student_id']) {
            http_response_code(403);
            die("Akses Ditolak!");
        }

        $del = $pdo->prepare("DELETE FROM karya WHERE id_karya = :id");
        $del->execute([':id' => $id]);
        delete_project_image(UPLOAD_DIR, $project['gambar']);
        set_flash('success', 'Project berhasil dihapus.');
    }
}

header('Location: index.php');
exit();
