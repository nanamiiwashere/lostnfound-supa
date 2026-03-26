<?php
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();
$u = currentUser();
$activePage = 'home';

if (($u['role'] ?? '') === 'staff'){
  header('Location: staff-laporan.php');
  exit();
}

$totalLaporan = $pdo -> prepare("SELECT COUNT(*) FROM laporan_kehilangan WHERE id_pelapor=? AND type='lost'");
$totalLaporan -> execute([$u['id']]);
$totalLaporan = (int)$totalLaporan -> fetchColumn();

$totalResolved = $pdo -> prepare("SELECT COUNT(*) FROM laporan_kehilangan WHERE id_pelapor=? AND status IN ('resolved', 'closed')");
$totalResolved -> execute([$u['id']]);
$totalResolved = (int)$totalResolved -> fetchColumn();

$totalOpen = $pdo -> prepare("SELECT COUNT(*) FROM laporan_kehilangan WHERE id_pelapor=? AND STATUS='open'");
$totalOpen -> execute([$u['id']]);
$totalOpen = (int)$totalOpen -> fetchColumn();

// Recent Reports
$stmt = $pdo -> prepare("SELECT * FROM laporan_kehilangan WHERE id_pelapor=? ORDER BY created_at DESC LIMIT 6");
$stmt -> execute([$u['id']]);
$recentItems = $stmt -> fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../dashboard/partials/style.css">
</head>
<body>

<?php require_once 'partials/sidebar.php'; ?>
 
<div class="main-wrap">
  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;">
        <i class="fas fa-bars"></i>
      </button>
      <div>
        <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Dashboard</span>
        <span style="color:#64748b;font-size:.82rem;margin-left:8px;">Halo, <?= htmlspecialchars(strtok($u['name']??'User',' ')) ?>!</span>
      </div>
    </div>
        <div class="d-flex align-items-center gap-2">
      <?php require_once 'partials/notification.php'; ?>
    <a href="buat-laporan.php" class="btn-accent"><i class="fas fa-plus"></i>Buat Laporan</a>
  </div>
</div>
 
  <div class="page-content">
 
    <!-- Stats -->
    <div class="row g-3 mb-4">
      <?php foreach([
        ['num'=>$totalLaporan, 'lbl'=>'Total Laporan',  'icon'=>'fa-file-alt',     'color'=>'#f97316'],
        ['num'=>$totalOpen,    'lbl'=>'Masih Open',     'icon'=>'fa-clock',        'color'=>'#818cf8'],
        ['num'=>$totalResolved,'lbl'=>'Berhasil',       'icon'=>'fa-check-circle', 'color'=>'#22c55e'],
        ['num'=>ucfirst($u['role']??'user'), 'lbl'=>'Role', 'icon'=>'fa-shield-alt','color'=>'#38bdf8'],
      ] as $s): ?>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $s['color'] ?>1a;">
            <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;"></i>
          </div>
          <div>
            <div class="stat-num" style="color:<?= $s['color'] ?>;"><?= $s['num'] ?></div>
            <div class="stat-lbl"><?= $s['lbl'] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Quick actions -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="dash-card p-4">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1rem;">Quick Action</div>
          <div class="d-flex flex-wrap gap-3">
            <a href="buat-laporan.php" class="btn-accent"><i class="fas fa-plus-circle"></i>Lapor Kehilangan</a>
            <a href="barang-temuan.php" style="background:rgba(129,140,248,.15);color:#818cf8;border:1px solid rgba(129,140,248,.2);" class="btn-accent"><i class="fas fa-search"></i>Cari Barang Temuan</a>
            <a href="laporan.php" style="background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.2);" class="btn-accent"><i class="fas fa-list"></i>Lihat Semua Laporan</a>
            <a href="profil.php" style="background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.08);" class="btn-accent"><i class="fas fa-user-cog"></i>Edit Profil</a>
          </div>
        </div>
      </div>
    </div>
 
    <!-- Recent laporan -->
    <div class="dash-card">
      <div class="dash-card-header">
        <div class="dash-card-title">Laporan Terbaru</div>
        <a href="laporan.php" style="color:#f97316;font-size:.82rem;font-weight:600;text-decoration:none;">Lihat semua →</a>
      </div>
      <?php if (empty($recentItems)): ?>
        <div class="empty-state">
          <i class="fas fa-file-alt"></i>
          Belum ada laporan.
          <div class="mt-3"><a href="buat-laporan.php" class="btn-accent">Buat Laporan Pertama</a></div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="dash-table">
            <thead>
              <tr><th>Nama Barang</th><th>Tipe</th><th>Status</th><th>Lokasi</th><th>Tanggal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach($recentItems as $item): ?>
              <tr>
                <td style="color:#fff;font-weight:600;"><?= htmlspecialchars($item['nama_barang']) ?></td>
                <td><span class="bdg bdg-<?= $item['type'] ?>"><?= strtoupper($item['type']) ?></span></td>
                <td><span class="bdg bdg-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span></td>
                <td style="color:#64748b;font-size:.82rem;"><?= htmlspecialchars($item['lokasi_kehilangan']) ?></td>
                <td style="color:#64748b;font-size:.8rem;"><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                <td>
                  <a href="detail-laporan.php?id=<?= $item['id_laporan'] ?>" class="btn-ghost-sm me-1"><i class="fas fa-eye"></i>Detail</a>
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