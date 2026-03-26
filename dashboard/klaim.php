<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();
$u = currentUser();
$activePage = 'klaim';


$stmt = $pdo->prepare("
    SELECT 
        p.id_pencocokan,
        p.tanggal_pencocokan,
        p.status_verifikasi,
        p.note,
        bt.id_barang,
        bt.nama_barang AS nama_barang_temuan,
        bt.lokasi_ditemukan,
        bt.category,
        bt.image,
        lk.id_laporan,
        lk.nama_barang AS nama_barang_laporan,
        lk.status AS status_laporan
    FROM pencocokan p
    INNER JOIN laporan_kehilangan lk ON p.id_laporan = lk.id_laporan
    INNER JOIN barang_temuan bt ON p.id_barang = bt.id_barang
    WHERE lk.id_pelapor = ?
    ORDER BY p.tanggal_pencocokan DESC
");
$stmt->execute([$u['id']]);
$klaimList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Riwayat Klaim — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../dashboard/partials/style.css"/>
</head>
<body>
<?php require_once 'partials/sidebar.php'; ?>

<div class="main-wrap">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;"><i class="fas fa-bars"></i></button>
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Riwayat Klaim</span>
    </div>
        <div class="d-flex align-items-center gap-2">
      <?php require_once 'partials/notification.php'; ?>
    <a href="barang-temuan.php" class="btn-accent"><i class="fas fa-search"></i>Cari Barang</a>
  </div>
</div>

  <div class="page-content">

    <div class="dash-card">
      <div class="dash-card-header">
        <div class="dash-card-title">
          <i class="fas fa-hand-holding me-2" style="color:#818cf8;"></i>
          Riwayat Klaim Saya
          <span style="color:#64748b;font-size:.8rem;font-weight:400;">(<?= count($klaimList) ?> klaim)</span>
        </div>
      </div>

      <?php if (empty($klaimList)): ?>
        <div class="empty-state">
          <i class="fas fa-hand-holding"></i>
          Kamu belum pernah mengajukan klaim.<br>
          <a href="barang-temuan.php" style="color:#818cf8;font-size:.85rem;">Cari barang temuanmu →</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="dash-table">
            <thead>
              <tr>
                <th>Barang Temuan</th>
                <th>Laporan Saya</th>
                <th>Tanggal Klaim</th>
                <th>Status</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($klaimList as $k): 
                $statusMap = [
                  'process'  => ['label'=>'Menunggu Verifikasi', 'class'=>'bdg-pending',  'icon'=>'fa-clock',        'color'=>'#f97316'],
                  'approved' => ['label'=>'Disetujui',           'class'=>'bdg-resolved', 'icon'=>'fa-check-circle', 'color'=>'#22c55e'],
                  'rejected' => ['label'=>'Ditolak',             'class'=>'bdg-closed',   'icon'=>'fa-times-circle', 'color'=>'#f87171'],
                ];
                $s = $statusMap[$k['status_verifikasi']] ?? ['label'=>ucfirst($k['status_verifikasi']), 'class'=>'', 'icon'=>'fa-circle', 'color'=>'#64748b'];
              ?>
              <tr>
                <td>
                  <div style="color:#86efac;font-weight:600;"><?= htmlspecialchars($k['nama_barang_temuan']) ?></div>
                  <div style="color:#64748b;font-size:.75rem;"><i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i><?= htmlspecialchars($k['lokasi_ditemukan']) ?></div>
                </td>
                <td>
                  <div style="color:#e2e8f0;"><?= htmlspecialchars($k['nama_barang_laporan']) ?></div>
                  <div style="color:#64748b;font-size:.75rem;">Laporan #<?= $k['id_laporan'] ?></div>
                </td>
                <td style="color:#64748b;font-size:.8rem;"><?= date('d M Y, H:i', strtotime($k['tanggal_pencocokan'])) ?></td>
                <td>
                  <span class="bdg <?= $s['class'] ?>">
                    <i class="fas <?= $s['icon'] ?> me-1"></i><?= $s['label'] ?>
                  </span>
                </td>
                <td style="color:#64748b;font-size:.8rem;"><?= htmlspecialchars($k['note'] ?? '—') ?></td>
                <td>
                  <?php if ($k['status_verifikasi'] === 'approved'): ?>
                    <a href="detail-laporan.php?id=<?= $k['id_laporan'] ?>" class="btn-accent" style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);font-size:.78rem;padding:5px 12px;">
                      <i class="fas fa-check-circle"></i>Konfirmasi
                    </a>
                  <?php else: ?>
                    <a href="item-detail.php?id=<?= $k['id_barang'] ?>" class="btn-ghost-sm" style="font-size:.78rem;">
                      <i class="fas fa-eye"></i>Detail
                    </a>
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