<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';

requireLogin();
if(($_SESSION['role']??'')!=='staff'){
    header('Location: index.php');
    exit();
}

$u = currentUser();
$activePage = 's-serah';

$success = '';
$error   = '';

// Handle input serah terima
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_serah'])) {
    $id_pencocokan  = (int)$_POST['id_pencocokan'];
    $nama_penerima  = trim($_POST['nama_penerima']);
    $keterangan     = trim($_POST['keterangan']);
    $id_pelapor     = (int)$_POST['id_pelapor'];

    // FIX: Validasi pencocokan benar-benar verified dan ambil id_laporan + id_barang sekaligus
    $cekCocokan = $pdo->prepare("
        SELECT p.id_laporan, p.id_barang, l.status AS status_laporan
        FROM pencocokan p
        LEFT JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
        WHERE p.id_pencocokan = ? AND p.status_verifikasi = 'approved'
    ");
    $cekCocokan->execute([$id_pencocokan]);
    $dataCocokan = $cekCocokan->fetch();

    if (!$dataCocokan) {
        $error = 'Pencocokan tidak ditemukan atau belum diverifikasi.';
    } elseif ($dataCocokan['status_laporan'] !== 'resolved') {
        // Hanya boleh serah terima jika user sudah konfirmasi kepemilikan (status=resolved)
        $error = 'Pemilik barang belum mengkonfirmasi kepemilikan. Minta pelapor konfirmasi terlebih dahulu di halaman laporan mereka.';
    } else {
        // Cek sudah ada serah terima untuk pencocokan ini?
        $cek = $pdo->prepare("SELECT COUNT(*) FROM serah_terima WHERE id_pencocokan=?");
        $cek->execute([$id_pencocokan]);
        if ($cek->fetchColumn() > 0) {
            $error = 'Serah terima untuk pencocokan ini sudah pernah dibuat.';
        } else {
            $pdo->prepare("INSERT INTO serah_terima (id_pencocokan,tanggal_serah_terima,nama_penerima,keterangan,id_petugas,id_pelapor) VALUES (?,NOW(),?,?,?,?)")
                ->execute([$id_pencocokan, $nama_penerima, $keterangan, $u['id'], $id_pelapor]);

            // FIX: Update laporan → closed DAN barang_temuan → resolved (dua-duanya)
            $pdo->prepare("UPDATE laporan_kehilangan SET status='closed' WHERE id_laporan=?")
                ->execute([$dataCocokan['id_laporan']]);
            $pdo->prepare("UPDATE barang_temuan SET status='resolved' WHERE id_barang=?")
                ->execute([$dataCocokan['id_barang']]);

            $success = 'Serah terima berhasil dicatat! Status laporan diubah ke Closed dan barang ke Resolved.';
        }
    }
}

$showForm   = isset($_GET['action']) && $_GET['action'] === 'add';
$preCocokan = (int)($_GET['id_pencocokan'] ?? 0);

// Ambil pencocokan verified yang belum ada serah terimanya
$verified = $pdo->query("
    SELECT p.id_pencocokan, p.id_laporan, b.nama_barang AS nama_barang_temuan,
           l.nama_barang AS nama_laporan, u.nama AS nama_pelapor,
           u.id_user AS id_pelapor, l.lokasi_kehilangan, l.status AS status_laporan
    FROM pencocokan p
    LEFT JOIN barang_temuan b ON p.id_barang = b.id_barang
    LEFT JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
    LEFT JOIN users u ON l.id_pelapor = u.id_user
    WHERE p.status_verifikasi = 'approved'
    AND p.id_pencocokan NOT IN (SELECT id_pencocokan FROM serah_terima WHERE id_pencocokan IS NOT NULL)
    AND l.status IN ('open','resolved')
    ORDER BY p.tanggal_pencocokan DESC
")->fetchAll();

// FIX: JOIN riwayat tidak duplikat kondisi
$riwayat = $pdo->query("
    SELECT st.*, p.id_pencocokan, b.nama_barang AS barang_temuan,
           l.nama_barang AS laporan_barang, up.nama AS nama_petugas,
           ul.nama AS nama_pelapor_asli
    FROM serah_terima st
    LEFT JOIN pencocokan p ON st.id_pencocokan = p.id_pencocokan
    LEFT JOIN barang_temuan b ON p.id_barang = b.id_barang
    LEFT JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
    LEFT JOIN users up ON st.id_petugas = up.id_user
    LEFT JOIN users ul ON st.id_pelapor = ul.id_user
    ORDER BY st.tanggal_serah_terima DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Serah Terima — Staff</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="partials/style.css">
</head>
<body>
<?php require_once 'partials/sidebar.php'; ?>
<div class="main-wrap">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;"><i class="fas fa-bars"></i></button>
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Serah Terima Barang</span>
    </div>
    <button onclick="toggleForm()" class="btn-accent" style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);"><i class="fas fa-plus"></i>Catat Serah Terima</button>
  </div>

  <div class="page-content">
    <?php if ($success): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>

    <?php if (!empty($verified)): ?>
    <div class="dash-card p-4 mb-4" style="border-color:rgba(249,115,22,.2);background:rgba(249,115,22,.04);">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:.8rem;">
        <i class="fas fa-exclamation-circle me-2" style="color:#f97316;"></i><?= count($verified) ?> Pencocokan Menunggu Serah Terima
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach($verified as $v): ?>
        <button onclick="prefillForm(<?= $v['id_pencocokan'] ?>, '<?= addslashes($v['nama_pelapor']??'') ?>', <?= $v['id_pelapor']??0 ?>)"
                class="btn-ghost-sm" style="color:<?= $v['status_laporan']==='resolved'?'#22c55e':'#f97316' ?>;border-color:<?= $v['status_laporan']==='resolved'?'rgba(34,197,94,.2)':'rgba(249,115,22,.2)' ?>;">
          <i class="fas fa-<?= $v['status_laporan']==='resolved'?'check-circle':'clock' ?>"></i>
          <?= htmlspecialchars($v['nama_laporan']??'Laporan #'.$v['id_laporan']) ?>
          <?php if ($v['status_laporan'] !== 'resolved'): ?>
            <span style="font-size:.7rem;opacity:.7;">(belum konfirmasi)</span>
          <?php endif; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div id="formSerah" class="dash-card p-4 mb-4" style="<?= $showForm?'':'display:none;' ?>">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;">
        <i class="fas fa-handshake me-2" style="color:#22c55e;"></i>Catat Serah Terima Barang
      </div>
      <form method="POST">
        <input type="hidden" name="add_serah" value="1"/>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Pencocokan <span style="color:#f87171;">*</span></label>
              <select name="id_pencocokan" id="sel_pencocokan" class="form-input" required>
                <option value="">— Pilih Pencocokan Terverifikasi —</option>
                <?php foreach($verified as $v): ?>
                  <option value="<?= $v['id_pencocokan'] ?>"
                          data-pelapor="<?= htmlspecialchars($v['nama_pelapor']??'') ?>"
                          data-id_pelapor="<?= $v['id_pelapor']??0 ?>"
                          data-status="<?= $v['status_laporan'] ?>"
                          <?= $preCocokan===$v['id_pencocokan']?'selected':'' ?>>
                    #<?= $v['id_pencocokan'] ?> — <?= htmlspecialchars($v['nama_barang_temuan']??'') ?> ↔ <?= htmlspecialchars($v['nama_laporan']??'') ?>
                    <?= $v['status_laporan'] !== 'resolved' ? ' ⚠ belum dikonfirmasi pemilik' : ' ✓ siap serah' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div id="status-warning" style="display:none;color:#f97316;font-size:.78rem;margin-top:6px;">
                <i class="fas fa-exclamation-triangle me-1"></i>Pelapor belum konfirmasi kepemilikan. Serah terima mungkin akan ditolak.
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Nama Penerima <span style="color:#f87171;">*</span></label>
              <input type="text" name="nama_penerima" id="inp_penerima" class="form-input" placeholder="Nama pemilik/penerima barang" required/>
            </div>
          </div>
          <input type="hidden" name="id_pelapor" id="inp_id_pelapor" value="0"/>
          <div class="col-12">
            <div class="form-group">
              <label>Keterangan</label>
              <textarea name="keterangan" class="form-input" rows="3" placeholder="Catatan tambahan, nomor identitas, tanda terima, dll…"></textarea>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn-accent" style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);"><i class="fas fa-check-circle"></i>Konfirmasi Serah Terima</button>
          <button type="button" class="btn-ghost-sm" onclick="toggleForm()">Batal</button>
        </div>
      </form>
    </div>

    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title"><i class="fas fa-handshake me-2" style="color:#22c55e;"></i>Riwayat Serah Terima <span style="color:#64748b;font-size:.8rem;font-weight:400;">(<?= count($riwayat) ?> entri)</span></span>
      </div>
      <?php if (empty($riwayat)): ?>
        <div class="empty-state"><i class="fas fa-handshake"></i>Belum ada serah terima tercatat.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="dash-table">
          <thead><tr>
            <th>#</th><th>Barang</th><th>Nama Penerima</th><th>Pelapor</th><th>Petugas</th><th>Keterangan</th><th>Tanggal</th>
          </tr></thead>
          <tbody>
            <?php foreach($riwayat as $s): ?>
            <tr>
              <td style="color:#64748b;"><?= $s['id_serah_terima'] ?></td>
              <td>
                <div style="color:#fff;font-weight:600;"><?= htmlspecialchars($s['barang_temuan']??$s['laporan_barang']??'—') ?></div>
                <div style="color:#64748b;font-size:.75rem;">via Pencocokan #<?= $s['id_pencocokan']??'—' ?></div>
              </td>
              <td style="color:#e2e8f0;font-weight:500;"><?= htmlspecialchars($s['nama_penerima']) ?></td>
              <td style="color:#94a3b8;"><?= htmlspecialchars($s['nama_pelapor_asli']??'—') ?></td>
              <td style="color:#94a3b8;"><?= htmlspecialchars($s['nama_petugas']??'—') ?></td>
              <td style="color:#64748b;font-size:.8rem;"><?= htmlspecialchars(substr($s['keterangan']??'—',0,50)) ?></td>
              <td style="color:#94a3b8;font-size:.8rem;"><?= date('d M Y, H:i', strtotime($s['tanggal_serah_terima'])) ?></td>
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
<script>
function toggleForm() {
  const f = document.getElementById('formSerah');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
function prefillForm(id_pencocokan, nama_pelapor, id_pelapor) {
  document.getElementById('sel_pencocokan').value  = id_pencocokan;
  document.getElementById('inp_penerima').value    = nama_pelapor;
  document.getElementById('inp_id_pelapor').value  = id_pelapor;
  checkStatus();
  const f = document.getElementById('formSerah');
  f.style.display = 'block';
  f.scrollIntoView({behavior:'smooth',block:'start'});
}
function checkStatus() {
  const opt = document.getElementById('sel_pencocokan').options[document.getElementById('sel_pencocokan').selectedIndex];
  const warn = document.getElementById('status-warning');
  if (opt && opt.dataset.status && opt.dataset.status !== 'resolved') {
    warn.style.display = 'block';
  } else {
    warn.style.display = 'none';
  }
}
document.getElementById('sel_pencocokan').addEventListener('change', function(){
  const opt = this.options[this.selectedIndex];
  document.getElementById('inp_penerima').value   = opt.dataset.pelapor  || '';
  document.getElementById('inp_id_pelapor').value = opt.dataset.id_pelapor || 0;
  checkStatus();
});
</script>
</body>
</html>