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
$activePage = 's-pencocokan';

$success = '';
$error   = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pencocokan'])) {
    $id_barang  = (int)$_POST['id_barang'];
    $id_laporan = (int)$_POST['id_laporan'];

    $exists = $pdo->prepare("SELECT COUNT(*) FROM pencocokan WHERE id_barang=? AND id_laporan=?");
    $exists->execute([$id_barang,$id_laporan]);
    if ($exists->fetchColumn() > 0) {
        $error = 'Kombinasi barang & laporan ini sudah pernah dicocokkan.';
    } else {
        $pdo->prepare("INSERT INTO pencocokan (id_barang,id_laporan,tanggal_pencocokan,status_verifikasi,id_petugas) VALUES (?,?,NOW(),'process',?)")
            ->execute([$id_barang,$id_laporan,$u['id']]);
        $success = 'Pencocokan berhasil dibuat!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_verifikasi'])) {
    $id_pencocokan = (int)$_POST['id_pencocokan'];
    $status        = $_POST['status_verifikasi'];
    if (in_array($status, ['process','approved','rejected'])) {
        $pdo->prepare("UPDATE pencocokan SET status_verifikasi=? WHERE id_pencocokan=? AND id_petugas=?")
            ->execute([$status, $id_pencocokan, $u['id']]);

        if ($status === 'approved') {
            $row = $pdo->prepare("SELECT id_laporan, id_barang FROM pencocokan WHERE id_pencocokan=?");
            $row->execute([$id_pencocokan]);
            $r = $row->fetch();
            if ($r) {

                $pdo->prepare("UPDATE laporan_kehilangan SET type='found' WHERE id_laporan=?")
                    ->execute([$r['id_laporan']]);
                $pdo->prepare("UPDATE barang_temuan SET status='open' WHERE id_barang=?")
                    ->execute([$r['id_barang']]);
            }
        }

        if ($status === 'rejected') {

            $row = $pdo->prepare("SELECT id_barang FROM pencocokan WHERE id_pencocokan=?");
            $row->execute([$id_pencocokan]);
            $r = $row->fetch();
            if ($r) {
                $pdo->prepare("UPDATE barang_temuan SET status='open' WHERE id_barang=?")
                    ->execute([$r['id_barang']]);
            }
        }

        $success = 'Status verifikasi diupdate.';
    }
}

$showForm    = isset($_GET['action']) && $_GET['action'] === 'add';
$preBarang   = (int)($_GET['id_barang'] ?? 0);
$preLaporan  = (int)($_GET['id_laporan'] ?? 0);


$laporanOpen = $pdo->query("SELECT l.id_laporan, l.nama_barang, l.lokasi_kehilangan, u.nama FROM laporan_kehilangan l LEFT JOIN users u ON l.id_pelapor=u.id_user WHERE l.status='open' ORDER BY l.created_at DESC")->fetchAll();
$barangOpen  = $pdo->query("SELECT * FROM barang_temuan WHERE status='open' ORDER BY created_at DESC")->fetchAll();


$pencocokan = $pdo->query("
    SELECT p.*, b.nama_barang, b.lokasi_ditemukan, l.nama_barang AS nama_laporan, l.lokasi_kehilangan, u.nama AS nama_petugas, ul.nama AS nama_pelapor, p.note
    FROM pencocokan p
    LEFT JOIN barang_temuan b ON p.id_barang=b.id_barang
    LEFT JOIN laporan_kehilangan l ON p.id_laporan=l.id_laporan
    LEFT JOIN users u ON p.id_petugas=u.id_user
    LEFT JOIN users ul ON l.id_pelapor=ul.id_user
    ORDER BY p.tanggal_pencocokan DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pencocokan — Staff</title>
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
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Pencocokan Barang</span>
    </div>
    <button onclick="toggleForm()" class="btn-accent" style="background:rgba(129,140,248,.15);color:#818cf8;border:1px solid rgba(129,140,248,.2);"><i class="fas fa-plus"></i>Buat Pencocokan</button>
  </div>
 
  <div class="page-content">
    <?php if ($success): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>
 
 
    <div id="formPencocokan" class="dash-card p-4 mb-4" style="<?= $showForm?'':'display:none;' ?>">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;">
        <i class="fas fa-link me-2" style="color:#818cf8;"></i>Buat Pencocokan Baru
      </div>
      <form method="POST">
        <input type="hidden" name="add_pencocokan" value="1"/>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Barang Temuan <span style="color:#f87171;">*</span></label>
              <select name="id_barang" class="form-input" required>
                <option value="">— Pilih Barang Temuan —</option>
                <?php foreach($barangOpen as $b): ?>
                  <option value="<?= $b['id_barang'] ?>" <?= $preBarang===$b['id_barang']?'selected':'' ?>>
                    #<?= $b['id_barang'] ?> — <?= htmlspecialchars($b['nama_barang']) ?> (<?= htmlspecialchars($b['lokasi_ditemukan']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($barangOpen)): ?>
                <div style="color:#f97316;font-size:.78rem;margin-top:6px;"><i class="fas fa-info-circle me-1"></i>Semua barang temuan sudah dicocokkan atau belum ada data.</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Laporan Kehilangan <span style="color:#f87171;">*</span></label>
              <select name="id_laporan" class="form-input" required>
                <option value="">— Pilih Laporan —</option>
                <?php foreach($laporanOpen as $l): ?>
                  <option value="<?= $l['id_laporan'] ?>" <?= $preLaporan===$l['id_laporan']?'selected':'' ?>>
                    #<?= $l['id_laporan'] ?> — <?= htmlspecialchars($l['nama_barang']) ?> (<?= htmlspecialchars($l['nama']??'?') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($laporanOpen)): ?>
                <div style="color:#f97316;font-size:.78rem;margin-top:6px;"><i class="fas fa-info-circle me-1"></i>Tidak ada laporan open saat ini.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn-accent" style="background:rgba(129,140,248,.15);color:#818cf8;border:1px solid rgba(129,140,248,.2);"><i class="fas fa-link"></i>Buat Pencocokan</button>
          <button type="button" class="btn-ghost-sm" onclick="toggleForm()">Batal</button>
        </div>
      </form>
    </div>
 
 
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title"><i class="fas fa-link me-2" style="color:#818cf8;"></i>Riwayat Pencocokan <span style="color:#64748b;font-size:.8rem;font-weight:400;">(<?= count($pencocokan) ?> entri)</span></span>
      </div>
      <?php if (empty($pencocokan)): ?>
        <div class="empty-state"><i class="fas fa-link"></i>Belum ada pencocokan.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="dash-table">
          <thead><tr>
            <th>#</th><th>Barang Temuan</th><th>Laporan</th><th>Pelapor</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            <?php foreach($pencocokan as $p): ?>
            <tr>
              <td style="color:#64748b;"><?= $p['id_pencocokan'] ?></td>
              <td>
                <div style="color:#86efac;font-weight:600;"><?= htmlspecialchars($p['nama_barang']??'—') ?></div>
                <div style="color:#64748b;font-size:.75rem;"><?= htmlspecialchars($p['lokasi_ditemukan']??'') ?></div>
              </td>
              <td>
                <div style="color:#fff;font-weight:600;"><?= htmlspecialchars($p['nama_laporan']??'—') ?></div>
                <div style="color:#64748b;font-size:.75rem;"><?= htmlspecialchars($p['lokasi_kehilangan']??'') ?></div>
              </td>
              <td style="color:#94a3b8;"><?= htmlspecialchars($p['nama_pelapor']??'—') ?></td>
              <td style="color:#94a3b8;font-size:.8rem;"><?= date('d M Y, H:i', strtotime($p['tanggal_pencocokan'])) ?></td>
              <td>
                <span class="bdg bdg-<?= $p['status_verifikasi']==='approved'?'resolved':($p['status_verifikasi']==='rejected'?'closed':'process') ?>">
                  <?= ucfirst($p['status_verifikasi']) ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <button class="btn-ghost-sm" onclick="openDetail(<?= $p['id_pencocokan'] ?>, <?= htmlspecialchars(json_encode([
                    'nama_barang'       => $p['nama_barang'] ?? '—',
                    'lokasi_ditemukan'  => $p['lokasi_ditemukan'] ?? '—',
                    'barang_image'      => $p['barang_image'] ?? '',
                    'category'          => $p['category'] ?? 'Other',
                    'nama_laporan'      => $p['nama_laporan'] ?? '—',
                    'lokasi_kehilangan' => $p['lokasi_kehilangan'] ?? '—',
                    'deskripsi_laporan' => $p['deskripsi_laporan'] ?? '—',
                    'nama_pelapor'      => $p['nama_pelapor'] ?? '—',
                    'email_pelapor'     => $p['email_pelapor'] ?? '—',
                    'note'              => $p['note'] ?? '',
                    'tanggal'           => date('d M Y, H:i', strtotime($p['tanggal_pencocokan'])),
                    'status'            => $p['status_verifikasi'],
                  ]), ENT_QUOTES) ?>)">
                    <i class="fas fa-eye"></i>Detail
                  </button>
                  <?php if ($p['status_verifikasi'] === 'process'): ?>
                  <form method="POST" class="d-flex gap-1">
                    <input type="hidden" name="id_pencocokan" value="<?= $p['id_pencocokan'] ?>"/>
                    <input type="hidden" name="update_verifikasi" value="1"/>
                    <button name="status_verifikasi" value="approved" type="submit" class="btn-accent" style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);padding:5px 10px;font-size:.75rem;">
                      <i class="fas fa-check"></i>Verifikasi
                    </button>
                    <button name="status_verifikasi" value="rejected" type="submit" class="btn-ghost-sm" style="color:#f87171;border-color:rgba(239,68,68,.2);">
                      <i class="fas fa-times"></i>Tolak
                    </button>
                  </form>
                  <?php elseif ($p['status_verifikasi'] === 'approved'): ?>
                    <a href="staff-serah.php?action=add&id_pencocokan=<?= $p['id_pencocokan'] ?>" class="btn-ghost-sm" style="color:#f97316;">
                      <i class="fas fa-handshake"></i>Serah Terima
                    </a>
                  <?php endif; ?>
                </div>
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
 
<!-- Modal Detail Klaim -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto;">
  <div class="dash-card p-4" style="width:100%;max-width:540px;margin:auto;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1.05rem;">
        <i class="fas fa-file-alt me-2" style="color:#818cf8;"></i>Detail Pencocokan
      </div>
      <button onclick="closeDetail()" style="background:none;border:none;color:#64748b;font-size:1.1rem;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
 
    <!-- Barang Temuan -->
    <div style="background:rgba(34,197,94,.05);border:1px solid rgba(34,197,94,.15);border-radius:12px;padding:1rem;margin-bottom:.8rem;">
      <div style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;"><i class="fas fa-box-open me-1" style="color:#22c55e;"></i>Barang Temuan</div>
      <div id="d_nama_barang" style="color:#fff;font-weight:700;font-size:.95rem;"></div>
      <div id="d_lokasi_ditemukan" style="color:#64748b;font-size:.8rem;margin-top:3px;"><i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i></div>
    </div>
 
    <!-- Laporan User -->
    <div style="background:rgba(129,140,248,.05);border:1px solid rgba(129,140,248,.15);border-radius:12px;padding:1rem;margin-bottom:.8rem;">
      <div style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;"><i class="fas fa-file-alt me-1" style="color:#818cf8;"></i>Laporan Kehilangan</div>
      <div id="d_nama_laporan" style="color:#fff;font-weight:700;font-size:.95rem;"></div>
      <div id="d_lokasi_kehilangan" style="color:#64748b;font-size:.8rem;margin-top:3px;"><i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i></div>
      <div id="d_deskripsi_laporan" style="color:#94a3b8;font-size:.82rem;margin-top:6px;line-height:1.6;"></div>
    </div>
 
    <!-- Alasan Klaim -->
    <div style="background:rgba(249,115,22,.05);border:1px solid rgba(249,115,22,.15);border-radius:12px;padding:1rem;margin-bottom:.8rem;">
      <div style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;"><i class="fas fa-comment-alt me-1" style="color:#f97316;"></i>Alasan / Bukti Klaim</div>
      <div id="d_note" style="color:#e2e8f0;font-size:.88rem;line-height:1.7;font-style:italic;"></div>
    </div>
 
    <!-- Info Pelapor -->
    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1rem;margin-bottom:1rem;">
      <div style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;"><i class="fas fa-user me-1"></i>Pelapor</div>
      <div class="d-flex justify-content-between flex-wrap gap-2">
        <div>
          <div id="d_nama_pelapor" style="color:#fff;font-weight:600;font-size:.88rem;"></div>
          <div id="d_email_pelapor" style="color:#64748b;font-size:.78rem;"></div>
        </div>
        <div style="text-align:right;">
          <div style="color:#64748b;font-size:.72rem;">Tanggal Klaim</div>
          <div id="d_tanggal" style="color:#94a3b8;font-size:.78rem;"></div>
        </div>
      </div>
    </div>
 
    <button onclick="closeDetail()" class="btn-ghost-sm w-100" style="justify-content:center;">Tutup</button>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleForm() {
  const f = document.getElementById('formPencocokan');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
function openDetail(id, data) {
  document.getElementById('d_nama_barang').textContent      = data.nama_barang;
  document.getElementById('d_lokasi_ditemukan').innerHTML   = '<i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i>' + data.lokasi_ditemukan;
  document.getElementById('d_nama_laporan').textContent     = data.nama_laporan;
  document.getElementById('d_lokasi_kehilangan').innerHTML  = '<i class="fas fa-map-marker-alt me-1" style="color:#f97316;"></i>' + data.lokasi_kehilangan;
  document.getElementById('d_deskripsi_laporan').textContent = data.deskripsi_laporan || '—';
  document.getElementById('d_note').textContent             = data.note || '(Tidak ada keterangan tambahan)';
  document.getElementById('d_nama_pelapor').textContent     = data.nama_pelapor;
  document.getElementById('d_email_pelapor').textContent    = data.email_pelapor;
  document.getElementById('d_tanggal').textContent          = data.tanggal;
  const m = document.getElementById('detailModal');
  m.style.display = 'flex';
}
function closeDetail() { document.getElementById('detailModal').style.display = 'none'; }
document.getElementById('detailModal').addEventListener('click', function(e){ if(e.target===this) closeDetail(); });
</script>
</body>
</html>