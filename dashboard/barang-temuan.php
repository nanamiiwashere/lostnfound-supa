<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();
$u = currentUser();
$activePage = 'temuan';

$q = trim($_GET['q'] ?? '');
$kategori = $_GET['category'] ?? 'all';

$where = "WHERE type ='found' AND status='open'";
$params = [];
if ($q){
    $where .= "AND (nama_barang LIKE ? OR deskripsi LIKE ? OR lokasi_ditemukan LIKE ?)";
    $params = array_merge($params, ["%$q%","%$q%","%$q%"]);
}

if ($kategori != 'all'){
    $where .= " AND category = ?"; $params[] = $kategori;
}

$stmt = $pdo -> prepare("SELECT * FROM barang_temuan $where ORDER BY created_at DESC");
$stmt -> execute($params);
$items = $stmt -> fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barang Temuan — LostnFound</title>
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
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Barang Temuan</span>
    </div>
  </div>
 
  <div class="page-content">
    <!-- Search & filter -->
    <form method="GET" class="dash-card p-3 mb-4">
      <div class="d-flex flex-wrap gap-3 align-items-end">
        <div style="flex:1;min-width:200px;">
          <label style="color:#94a3b8;font-size:.78rem;font-weight:500;display:block;margin-bottom:5px;">Cari Barang</label>
          <div style="position:relative;">
            <input type="text" name="q" class="form-input" placeholder="Nama barang, lokasi..." value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;"/>
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#475569;font-size:.82rem;"></i>
          </div>
        </div>
        <div>
          <label style="color:#94a3b8;font-size:.78rem;font-weight:500;display:block;margin-bottom:5px;">Kategori</label>
          <select name="category" class="form-input" style="width:auto;padding:8px 14px;">
            <option value="all">Semua</option>
            <?php foreach(['Electronics','Accessories','Bags','Keys','Documents','Pets','Jewelry','Other'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $kategori===$cat?'selected':'' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-accent" style="padding:8px 18px;"><i class="fas fa-search"></i>Cari</button>
        <?php if ($q||$kategori!=='all'): ?>
          <a href="barang-temuan.php" class="btn-ghost-sm" style="padding:8px 14px;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
 
    <!-- Results -->
    <?php if (empty($items)): ?>
      <div class="dash-card">
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          Tidak ada barang temuan<?= $q ? " untuk \"$q\"" : '' ?>.
        </div>
      </div>
    <?php else: ?>
      <div style="color:#64748b;font-size:.82rem;margin-bottom:12px;"><?= count($items) ?> barang ditemukan</div>
      <div class="row g-3">
        <?php
        $catIcons=['Electronics'=>'fa-mobile-alt','Accessories'=>'fa-wallet','Pets'=>'fa-paw','Bags'=>'fa-shopping-bag','Keys'=>'fa-key','Jewelry'=>'fa-gem','Documents'=>'fa-id-card','Other'=>'fa-box'];
        foreach($items as $item):
          $icon = $catIcons[$item['category']??'Other'] ?? 'fa-box';
        ?>
        <div class="col-sm-6 col-xl-4">
          <div class="dash-card h-100" style="transition:transform .2s,border-color .2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(249,115,22,.2)'" onmouseout="this.style.transform='';this.style.borderColor=''">
            <!-- Image -->
            <div style="position:relative;overflow:hidden;border-radius:16px 16px 0 0;">
              <?php if (!empty($item['image'])): ?>
                <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" style="width:100%;height:160px;object-fit:cover;" alt=""/>
              <?php else: ?>
                <div style="width:100%;height:160px;background:linear-gradient(135deg,#1a2332,#111927);display:flex;align-items:center;justify-content:center;">
                  <i class="fas <?= $icon ?> fa-2x" style="color:rgba(255,255,255,.1);"></i>
                </div>
              <?php endif; ?>
              <span class="bdg bdg-found" style="position:absolute;top:10px;left:10px;">FOUND</span>
            </div>
            <div style="padding:1rem;">
              <div style="color:#64748b;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;"><?= htmlspecialchars($item['category']??'Other') ?></div>
              <div style="color:#fff;font-weight:700;margin:4px 0 8px;font-size:.95rem;"><?= htmlspecialchars($item['nama_barang']) ?></div>
              <div style="color:#64748b;font-size:.8rem;margin-bottom:12px;"><i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i><?= htmlspecialchars($item['lokasi_ditemukan']) ?></div>
              <div style="color:#475569;font-size:.75rem;margin-bottom:12px;"><i class="fas fa-calendar me-1"></i><?= date('d M Y', strtotime($item['created_at'])) ?></div>
              <a href="./item-detail.php?id=<?= $item['id_barang'] ?>" class="btn-accent w-100" style="justify-content:center;font-size:.82rem;">
                <i class="fas fa-eye"></i>Lihat Detail & Klaim
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 