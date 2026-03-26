<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();

$u = currentUser();
$activePage = 'laporan';

$filterStatus = $_GET['status'] ?? 'all';
$filterType   = $_GET['type'] ?? 'all';

$where = "WHERE id_pelapor = ?";
$params = [$u['id']];
if ($filterStatus !== 'all'){
    $where .= " AND status = ?"; $params [] = $filterStatus;
}
if ($filterType !== 'all'){
    $where .= " AND type = ?"; $params [] = $filterType;
}

$stmt = $pdo -> prepare("SELECT * FROM laporan_kehilangan $where ORDER BY created_at DESC");
$stmt -> execute($params);
$laporan = $stmt -> fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Saya — LostnFound</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={
        corePlugins:{
            preflight: false
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../dashboard/partials/style.css">
</head>
<body>
    <?php require_once 'partials/sidebar.php'; ?>

<div class="main-wrap">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;"><i class="fas fa-bars"></i></button>
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Laporan Saya</span>
    </div>
        <div class="d-flex align-items-center gap-2">
      <?php require_once 'partials/notification.php'; ?>
    <a href="buat-laporan.php" class="btn-accent"><i class="fas fa-plus"></i>Buat Laporan</a>
  </div>
</div>

  <div class="page-content">
    <!-- Filter -->
    <div class="dash-card p-3 mb-4">
      <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
        <div>
          <label style="color:#94a3b8;font-size:.78rem;font-weight:500;display:block;margin-bottom:5px;">Status</label>
          <select name="status" class="form-input" style="width:auto;padding:8px 14px;">
            <option value="all"      <?= $filterStatus==='all'?'selected':'' ?>>Semua Status</option>
            <option value="open"     <?= $filterStatus==='open'?'selected':'' ?>>Open</option>
            <option value="resolved" <?= $filterStatus==='resolved'?'selected':'' ?>>Resolved</option>
            <option value="closed"   <?= $filterStatus==='closed'?'selected':'' ?>>Closed</option>
          </select>
        </div>
        <div>
          <label style="color:#94a3b8;font-size:.78rem;font-weight:500;display:block;margin-bottom:5px;">Tipe</label>
          <select name="type" class="form-input" style="width:auto;padding:8px 14px;">
            <option value="all"   <?= $filterType==='all'?'selected':'' ?>>Semua Tipe</option>
            <option value="lost"  <?= $filterType==='lost'?'selected':'' ?>>Lost</option>
            <option value="found" <?= $filterType==='found'?'selected':'' ?>>Found</option>
          </select>
        </div>
        <button type="submit" class="btn-accent" style="padding:8px 18px;">Filter</button>
        <a href="laporan.php" class="btn-ghost-sm" style="padding:8px 14px;">Reset</a>
      </form>
    </div>
 
    <div class="dash-card">
      <div class="dash-card-header">
        <div class="dash-card-title">Semua Laporan <span style="color:#64748b;font-weight:400;font-size:.85rem;">(<?= count($laporan) ?> laporan)</span></div>
      </div>
      <?php if (empty($laporan)): ?>
        <div class="empty-state">
          <i class="fas fa-file-alt"></i>
          Belum ada laporan<?= $filterStatus!=='all'||$filterType!=='all' ? ' dengan filter ini' : '' ?>.
          <div class="mt-3"><a href="buat-laporan.php" class="btn-accent">Buat Laporan</a></div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="dash-table">
            <thead>
              <tr><th>Nama Barang</th><th>Tipe</th><th>Status</th><th>Lokasi</th><th>Tanggal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach($laporan as $item): ?>
              <tr>
                <td>
                  <div style="color:#fff;font-weight:600;"><?= htmlspecialchars($item['nama_barang']) ?></div>
                  <div style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars(substr($item['deskripsi']??'',0,50)) ?><?= strlen($item['deskripsi']??'')>50?'...':'' ?></div>
                </td>
                <td><span class="bdg bdg-<?= $item['type'] ?>"><?= strtoupper($item['type']) ?></span></td>
                <td><span class="bdg bdg-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span></td>
                <td style="color:#64748b;font-size:.82rem;"><?= htmlspecialchars($item['lokasi_kehilangan']) ?></td>
                <td style="color:#64748b;font-size:.8rem;"><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                <td>
                  <a href="detail-laporan.php?id=<?= $item['id_laporan'] ?>" class="btn-ghost-sm me-1"><i class="fas fa-eye"></i></a>
                  <?php if ($item['status']==='open'): ?>
                    <a href="edit-laporan.php?id=<?= $item['id_laporan'] ?>" class="btn-ghost-sm me-1" style="color:#818cf8;border-color:rgba(129,140,248,.2);"><i class="fas fa-edit"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>