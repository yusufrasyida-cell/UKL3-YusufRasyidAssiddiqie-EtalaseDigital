<?php
require_once __DIR__ . '/koneksi.php';
require_once 'middleware/auth_check.php';

check_login();

$cari = trim($_GET['cari'] ?? '');

$sql = "SELECT karya.id_karya AS id, karya.id_siswa AS student_id,
               students.id_user AS user_id, karya.judul AS nama_project,
               COALESCE(karya.deskripsi, '') AS deskripsi,
               COALESCE(karya.kategori, '') AS kategori,
               karya.gambar, karya.status,
               karya.tanggal_upload AS created_at, students.nama_lengkap AS pembuat
        FROM karya
        JOIN students ON students.id_siswa = karya.id_siswa";
$conditions = [];
$params = [];
if (!is_staff()) {
    $conditions[] = "(karya.status = 'published' OR students.id_user = :user_id)";
    $params[':user_id'] = (int) $_SESSION['user_id'];
}
if ($cari !== '') {
    $conditions[] = "(karya.judul LIKE :judul OR students.nama_lengkap LIKE :nama OR karya.kategori LIKE :kategori)";
    $params[':judul'] = "%$cari%";
    $params[':nama'] = "%$cari%";
    $params[':kategori'] = "%$cari%";
}
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY karya.tanggal_upload DESC, karya.id_karya DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$base   = '';
$active = 'galeri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Etalase Digital Stella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/popup.css">
</head>
<body>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="container my-5">
    <?= render_flash() ?>

    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-5">
            <h2 class="fw-bold mb-1">Galeri Project Siswa</h2>
            <p class="text-muted mb-0">Kumpulan karya dan pameran digital siswa Stella</p>
        </div>
        <div class="col-md-7">
            <form method="GET" action="index.php" class="d-flex gap-2 justify-content-md-end align-items-center">
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="cari" class="form-control" placeholder="Cari judul, nama siswa, atau kategori" value="<?= e($cari) ?>">
                    <?php if ($cari !== ''): ?>
                        <a href="index.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if ($_SESSION['role'] === 'siswa'): ?>
                    <a href="project_tambah.php" class="btn btn-success rounded-pill px-4 text-nowrap"><i class="bi bi-plus-lg me-1"></i> Tambah Karya</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $p): ?>
                <?php $canManage = is_staff() || $_SESSION['user_id'] == $p['user_id']; ?>
                <div class="col-md-6 col-lg-4">
                    <!-- Klik kartu => popup detail (data dibaca oleh JS di bawah) -->
                    <div class="card h-100 project-card" tabindex="0" role="button"
                         data-id="<?= (int) $p['id'] ?>"
                         data-judul="<?= e($p['nama_project']) ?>"
                         data-pembuat="<?= e($p['pembuat']) ?>"
                         data-deskripsi="<?= e($p['deskripsi']) ?>"
                         data-kategori="<?= e($p['kategori'] ?? '') ?>"
                         data-status="<?= e($p['status']) ?>"
                         data-gambar="uploads/<?= e($p['gambar']) ?>"
                         data-tanggal="<?= e(date('d M Y', strtotime($p['created_at']))) ?>"
                         data-manage="<?= $canManage ? '1' : '0' ?>">
                        <img src="uploads/<?= e($p['gambar']) ?>" class="card-img-top project-img" alt="Gambar Project">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= e($p['nama_project']) ?></h5>
                            <p class="card-text text-muted small mb-2"><i class="bi bi-person-circle"></i> Oleh: <b><?= e($p['pembuat']) ?></b></p>
                            <?php if ($p['kategori']): ?><span class="badge bg-secondary align-self-start"><?= e($p['kategori']) ?></span><?php endif; ?>
                            <?php if ($p['status'] === 'draft'): ?><span class="badge bg-warning text-dark align-self-start mt-1">Draft</span><?php endif; ?>
                            <p class="card-text flex-grow-1"><?= e(mb_strimwidth($p['deskripsi'], 0, 120, '...')) ?></p>
                            <div class="text-primary small fw-semibold mt-2">
                                <i class="bi bi-arrows-fullscreen"></i> Klik untuk lihat detail
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-folder-x text-muted display-1"></i>
                <p class="text-muted mt-3">Tidak ada project atau karya yang ditemukan.</p>
                <?php if ($cari !== ''): ?>
                    <a href="index.php" class="btn btn-sm btn-outline-primary mt-2">Reset Pencarian</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Popup detail project (gaya dari etalase_stella_lkpd) -->
<div id="projectPopup" class="popup-overlay" aria-hidden="true">
    <div class="popup-content" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
        <button type="button" class="popup-close" data-popup-close aria-label="Tutup">&times;</button>
        <img id="popupImg" class="popup-img" src="" alt="Detail Karya">
        <div class="popup-body">
            <h2 id="popupTitle" class="popup-title"></h2>
            <div id="popupAuthor" class="popup-meta"></div>
            <div id="popupDate" class="popup-meta"></div>
            <p id="popupDesc" class="popup-desc"></p>
            <div id="popupCategory" class="popup-meta"></div>
            <div id="popupStatus" class="popup-meta"></div>

            <div id="popupActions" class="popup-actions" style="display: none;">
                <a id="popupEdit" href="#" class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-pencil-square"></i> Edit</a>
                <form id="popupDeleteForm" method="POST" action="project_hapus.php" onsubmit="return confirm('Hapus karya ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="popupDeleteId" value="">
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold"><i class="bi bi-trash"></i> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/popup.js"></script>
<script>
(function () {
    var popup = document.getElementById('projectPopup');

    function openProject(card) {
        var d = card.dataset;

        document.getElementById('popupImg').src = d.gambar;
        document.getElementById('popupTitle').textContent = d.judul;
        document.getElementById('popupDesc').textContent = d.deskripsi;

        var author = document.getElementById('popupAuthor');
        author.textContent = 'Oleh: ';
        var strong = document.createElement('b');
        strong.textContent = d.pembuat;
        author.appendChild(strong);

        document.getElementById('popupDate').textContent = 'Dibuat pada: ' + d.tanggal;
        document.getElementById('popupCategory').textContent = d.kategori ? 'Kategori: ' + d.kategori : '';
        document.getElementById('popupStatus').textContent = d.status === 'draft' ? 'Status: Draft' : 'Status: Published';

        var actions = document.getElementById('popupActions');
        if (d.manage === '1') {
            document.getElementById('popupEdit').href = 'project_edit.php?id=' + encodeURIComponent(d.id);
            document.getElementById('popupDeleteId').value = d.id;
            actions.style.display = 'flex';
        } else {
            actions.style.display = 'none';
        }

        StellaPopup.open(popup);
    }

    document.querySelectorAll('.project-card').forEach(function (card) {
        card.addEventListener('click', function () { openProject(card); });
        card.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                openProject(card);
            }
        });
    });
})();
</script>

</body>
</html>
