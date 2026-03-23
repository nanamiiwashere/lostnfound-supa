<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();

$u  = currentUser();
$activePage = 'item-detail';
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ../items.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT bt.*, u.nama AS nama_petugas, u.email AS email_petugas
    FROM barang_temuan bt
    LEFT JOIN users u ON u.id_user = bt.id_petugas
    WHERE bt.id_barang = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: ../items.php');
    exit();
}

$myLaporan = $pdo->prepare("
    SELECT * FROM laporan_kehilangan
    WHERE id_pelapor = ? AND status = 'open'
    ORDER BY created_at DESC
");
$myLaporan->execute([$u['id']]);
$laporanList = $myLaporan->fetchAll();

$claimSuccess = false;
$claimError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim'])) {
    $idLaporan = (int)($_POST['id_laporan'] ?? 0);
    if (!$idLaporan) {
        $claimError = 'Pilih laporan kehilangan kamu terlebih dahulu.';
    } else {
        $cekLaporan = $pdo->prepare("SELECT * FROM laporan_kehilangan WHERE id_laporan=? AND id_pelapor=?");
        $cekLaporan->execute([$idLaporan, $u['id']]);
        if (!$cekLaporan->fetch()) {
            $claimError = 'Laporan tidak valid.';
        } else {
            // Cek klaim aktif saja — rejected boleh klaim ulang
            $cekClaim = $pdo->prepare("SELECT * FROM pencocokan WHERE id_laporan=? AND id_barang=? AND status_verifikasi IN ('process','approved')");
            $cekClaim->execute([$idLaporan, $id]);
            if ($cekClaim->fetch()) {
                $claimError = 'Kamu sudah pernah mengajukan klaim aktif untuk barang ini.';
            } else {

                $pdo->prepare("
                    INSERT INTO pencocokan (id_laporan, id_barang, id_petugas, tanggal_pencocokan, note, status_verifikasi)
                    VALUES (?, ?, ?, NOW(), ?, 'process')
                ")->execute([$idLaporan, $id, $item['id_petugas'], $_POST['note'] ?? '']);
                $claimSuccess = true;
            }
        }
    }
}

$catIcons = [
    'Electronics'=>'fa-mobile-alt','Accessories'=>'fa-wallet',
    'Pets'=>'fa-paw','Bags'=>'fa-shopping-bag','Keys'=>'fa-key',
    'Jewelry'=>'fa-gem','Documents'=>'fa-id-card','Other'=>'fa-box',
    'Clothing'=>'fa-tshirt'
];
$icon = $catIcons[$item['category'] ?? 'Other'] ?? 'fa-box';

$statusClass = match($item['status']) {
    'open'     => 'open',
    'resolved' => 'resolved',
    'matched'  => 'matched',
    default    => 'closed'
};
$statusLabel = match($item['status']) {
    'open'     => 'Tersedia',
    'resolved' => 'Sudah Dikembalikan',
    'matched'  => 'Sedang Diproses',
    default    => ucfirst($item['status'])
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($item['nama_barang']) ?> — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="partials/style.css"/>
</head>
<body>
<?php require_once 'partials/sidebar.php'; ?>
 
<div class="main-wrap">
 

  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;"><i class="fas fa-bars"></i></button>
      <div>
        <a href="barang-temuan.php" style="color:#64748b;font-size:.82rem;text-decoration:none;"><i class="fas fa-arrow-left me-1"></i>Barang Temuan</a>
        <span style="color:#334155;margin:0 6px;">/</span>
        <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;"><?= htmlspecialchars($item['nama_barang']) ?></span>
      </div>
    </div>
  </div>
 

  <div class="page-content">
 
    <?php if ($claimSuccess): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><strong>Klaim berhasil diajukan!</strong> Petugas akan memverifikasi. Pantau di <a href="klaim.php" style="color:#86efac;font-weight:700;">Riwayat Klaim</a>.</div>
    <?php endif; ?>
    <?php if (!empty($claimError)): ?>
      <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($claimError) ?></div>
    <?php endif; ?>
 
    <div class="row g-4">
 

      <div class="col-lg-8">
 
        <?php if (!empty($item['image'])): ?>
          <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" class="w-100 rounded-3 mb-4" style="max-height:320px;object-fit:cover;" alt="foto barang"/>
        <?php else: ?>
          <div class="dash-card mb-4 d-flex align-items-center justify-content-center" style="height:200px;">
            <i class="fas <?= $icon ?> fa-4x" style="color:rgba(255,255,255,.08);"></i>
          </div>
        <?php endif; ?>
 

        <div class="dash-card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
              <h4 style="font-family:'Clash Display',sans-serif;color:#fff;margin:0;"><?= htmlspecialchars($item['nama_barang']) ?></h4>
              <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="bdg" style="background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.08);">
                  <i class="fas <?= $icon ?> me-1"></i><?= htmlspecialchars($item['category'] ?? 'Other') ?>
                </span>
              </div>
            </div>
            <span class="status-pill <?= $statusClass ?>">
              <i class="fas <?= $item['status']==='open' ? 'fa-check-circle' : ($item['status']==='resolved' ? 'fa-flag-checkered' : 'fa-clock') ?>"></i>
              <?= $statusLabel ?>
            </span>
          </div>
 
          <div class="row g-3">
            <div class="col-12">
              <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Deskripsi</div>
              <div style="color:#e2e8f0;font-size:.9rem;"><?= nl2br(htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi.')) ?></div>
            </div>
            <div class="col-sm-6">
              <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;"><i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i>Lokasi Ditemukan</div>
              <div style="color:#e2e8f0;font-size:.9rem;"><?= htmlspecialchars($item['lokasi_ditemukan']) ?></div>
            </div>
            <div class="col-sm-6">
              <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;"><i class="fas fa-calendar me-1" style="color:#f97316;"></i>Tanggal Ditemukan</div>
              <div style="color:#e2e8f0;font-size:.9rem;"><?= date('d F Y', strtotime($item['tanggal_ditemukan'])) ?></div>
            </div>
            <div class="col-sm-6">
              <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;"><i class="fas fa-clock me-1" style="color:#f97316;"></i>Diinput Petugas</div>
              <div style="color:#e2e8f0;font-size:.9rem;"><?= date('d F Y, H:i', strtotime($item['created_at'])) ?></div>
            </div>
          </div>
        </div>

        <?php if (!empty($item['nama_petugas'])): ?>
        <div class="dash-card p-4" style="background:rgba(249,115,22,.04);border-color:rgba(249,115,22,.15);">
          <div style="color:#64748b;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;"><i class="fas fa-user-tie me-1"></i>Diinput oleh Petugas</div>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:50%;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-user-tie" style="color:#f97316;font-size:.85rem;"></i>
            </div>
            <div>
              <div style="color:#e2e8f0;font-weight:600;font-size:.9rem;"><?= htmlspecialchars($item['nama_petugas']) ?></div>
              <a href="mailto:<?= htmlspecialchars($item['email_petugas']) ?>" style="color:#f97316;font-size:.82rem;text-decoration:none;">
                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($item['email_petugas']) ?>
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>
 
      </div>
 

      <div class="col-lg-4">
 
        <?php if ($item['status'] === 'open' && !$claimSuccess): ?>
        <div class="dash-card p-4 mb-4" style="border-color:rgba(249,115,22,.2);">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1.05rem;margin-bottom:.5rem;">
            <i class="fas fa-hand-holding me-2" style="color:#f97316;"></i>Ini Barang Saya!
          </div>
          <p style="color:#64748b;font-size:.85rem;margin-bottom:1.4rem;">
            Jika kamu merasa ini adalah barang kamu yang hilang, pilih laporan kehilangan yang sesuai dan ajukan klaim.
          </p>
 
          <?php if (empty($laporanList)): ?>
            <div style="background:rgba(249,115,22,.06);border:1px dashed rgba(249,115,22,.2);border-radius:12px;padding:1.2rem;text-align:center;margin-bottom:1rem;">
              <i class="fas fa-file-alt fa-2x mb-2 d-block" style="color:rgba(249,115,22,.4);"></i>
              <div style="color:#94a3b8;font-size:.85rem;margin-bottom:10px;">Kamu belum punya laporan kehilangan aktif.</div>
              <a href="buat-laporan.php" class="btn-accent" style="font-size:.85rem;padding:9px 20px;">
                <i class="fas fa-plus-circle"></i>Buat Laporan Dulu
              </a>
            </div>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="claim" value="1"/>
              <div class="mb-3">
                <label style="display:block;color:#94a3b8;font-size:.82rem;font-weight:500;margin-bottom:7px;">Pilih Laporan Kehilangan Kamu</label>
                <select name="id_laporan" class="claim-select" required>
                  <option value="">-- Pilih laporan --</option>
                  <?php foreach ($laporanList as $lap): ?>
                    <option value="<?= $lap['id_laporan'] ?>">
                      <?= htmlspecialchars($lap['nama_barang']) ?> — <?= date('d M Y', strtotime($lap['created_at'])) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-4">
                <label style="display:block;color:#94a3b8;font-size:.82rem;font-weight:500;margin-bottom:7px;">Keterangan Tambahan <span style="color:#64748b;font-weight:400;">(opsional)</span></label>
                <textarea name="note" class="claim-textarea" rows="3"
                          placeholder="Jelaskan ciri khas barang kamu yang membuktikan ini milik kamu..."></textarea>
              </div>
              <button type="submit" class="btn-accent w-100" style="justify-content:center;"
                      onclick="return confirm('Yakin ingin mengajukan klaim untuk barang ini?')">
                <i class="fas fa-hand-holding"></i>Ajukan Klaim
              </button>
            </form>
          <?php endif; ?>
        </div>
 
        <?php elseif ($item['status'] === 'resolved'): ?>
        <div class="dash-card p-4 mb-4" style="border-color:rgba(129,140,248,.2);text-align:center;">
          <i class="fas fa-flag-checkered fa-2x mb-3 d-block" style="color:#818cf8;"></i>
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1rem;margin-bottom:6px;">Barang Sudah Dikembalikan</div>
          <div style="color:#64748b;font-size:.85rem;">Barang ini sudah berhasil dikembalikan ke pemiliknya.</div>
        </div>
 
        <?php elseif ($item['status'] === 'matched'): ?>
        <div class="dash-card p-4 mb-4" style="border-color:rgba(249,115,22,.2);text-align:center;">
          <i class="fas fa-clock fa-2x mb-3 d-block" style="color:#f97316;"></i>
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1rem;margin-bottom:6px;">Sedang Diproses</div>
          <div style="color:#64748b;font-size:.85rem;">Ada klaim yang sedang diverifikasi oleh petugas.</div>
        </div>
        <?php endif; ?>
 

        <div class="dash-card p-4" style="background:rgba(255,255,255,.02);">
          <div style="color:#94a3b8;font-weight:700;font-size:.82rem;margin-bottom:10px;">
            <i class="fas fa-lightbulb me-2" style="color:#f97316;"></i>Tips Klaim
          </div>
          <ul style="color:#64748b;font-size:.8rem;margin:0;padding-left:1.2rem;line-height:1.9;">
            <li>Pastikan laporan kehilangan kamu sudah sesuai</li>
            <li>Tambahkan ciri khas unik di keterangan</li>
            <li>Petugas akan menghubungi via email</li>
            <li>Proses verifikasi 1–3 hari kerja</li>
          </ul>
        </div>
 
      </div>
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>