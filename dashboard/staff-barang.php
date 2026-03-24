<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';

requireLogin();
if(($_SESSION['role']?? '') !== 'staff'){
    header('Location: index.php');
    exit();
}
$u = currentUser();
$activePage = 's-barang';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['add_barang'])){
        $nama = trim($_POST['nama_barang']);
        $deskripsi = trim($_POST['deskripsi']);
        $category = $_POST['category'];
        $lokasi = trim($_POST['lokasi_ditemukan']);
        $tanggal = $_POST['tanggal_ditemukan'];
        $type = $_POST['type'];

        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'barang_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imageName);
        }

        $pdo -> prepare("INSERT INTO barang_temuan (nama_barang, deskripsi, category, lokasi_ditemukan, tanggal_ditemukan, type, image, id_petugas, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')")
         -> execute([$nama, $deskripsi, $category, $lokasi, $tanggal, $type, $imageName, $u['id']]);
        $success = isset($_GET['success']) ? 'Barang temuan berhasil ditambahkan!' : '';
        header("Location: staff-barang.php?success=1");
        exit();

    }

    if (isset($_POST['delete_barang'])){
        $id = (int)$_POST['id_barang'];
        $pdo -> prepare("DELETE FROM pencocokan WHERE id_barang=?") -> execute([$id]);
        $pdo -> prepare("DELETE FROM barang_temuan WHERE id_barang=?") -> execute([$id]);
        $success = 'Barang temuan berhasil dihapus.';
        header("Location: staff-barang.php?deleted=1");
        exit();
    }

    if (isset($_POST['update_status'])){
        $id = (int)$_POST['id_barang'];
        $status = $_POST['status'];
        if (in_array($status, ['open', 'resolved', 'closed'])){
            $pdo -> prepare("UPDATE barang_temuan SET status=? WHERE id_barang=?") -> execute([$status, $id]);
        } 
        $success = 'Status berhasil diupdate.';
    }
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'add';

$search = trim($_GET['q'] ?? '');
$where = $search ? "WHERE nama_barang LIKE ? OR lokasi_ditemukan LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%"] : [];
$stmt = $pdo -> prepare("SELECT * FROM barang_temuan $where ORDER BY created_at DESC");
$stmt -> execute($params);
$items = $stmt -> fetchAll();


$categories = ['Electronics','Bags','Documents','Accessories','Clothing','Jewelry','Keys','Pets','Other'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barang Temuan — Staff</title>
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
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Barang Temuan</span>
    </div>
    <button onclick="toggleForm()" class="btn-accent"><i class="fas fa-plus"></i>Input Barang Temuan</button>
  </div>
 
  <div class="page-content">
    <?php if ($success): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert-success mb-4"><i class="fas fa-trash me-2"></i>Barang temuan berhasil dihapus.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i>Barang temuan berhasil ditambahkan!</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>
 
    <div id="formBarang" class="dash-card p-4 mb-4" style="<?= $showForm?'':'display:none;' ?>">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;font-size:1.05rem;">
        <i class="fas fa-box-open me-2" style="color:#86efac;"></i>Input Barang Temuan Baru
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_barang" value="1"/>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-group">
              <label>Nama Barang <span style="color:#f87171;">*</span></label>
              <input type="text" name="nama_barang" class="form-input" placeholder="Contoh: Dompet Kulit Hitam" required/>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Kategori</label>
              <select name="category" class="form-input">
                <?php foreach($categories as $c): ?>
                  <option value="<?= $c ?>"><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Tipe</label>
              <select name="type" class="form-input">
                <option value="found">Found (ditemukan)</option>
                <option value="lost">Lost (dicari)</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea name="deskripsi" class="form-input" rows="3" placeholder="Ciri-ciri barang, warna, merek, dll…"></textarea>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Foto Barang</label>
              <div class="upload-area" onclick="document.getElementById('imgInput').click()">
                <i class="fas fa-camera" style="font-size:1.5rem;color:#64748b;margin-bottom:8px;display:block;"></i>
                <div style="color:#64748b;font-size:.85rem;">Klik untuk upload foto</div>
                <img id="imgPreview" class="preview-img" alt=""/>
              </div>
              <input type="file" id="imgInput" name="image" accept="image/*" style="display:none;" onchange="previewImg(this)"/>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Lokasi Ditemukan <span style="color:#f87171;">*</span></label>
              <input type="text" name="lokasi_ditemukan" class="form-input" placeholder="Contoh: Stasiun Manggarai, Gerbong 3" required/>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Tanggal Ditemukan <span style="color:#f87171;">*</span></label>
              <input type="date" name="tanggal_ditemukan" class="form-input" value="<?= date('Y-m-d') ?>" required/>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn-accent"><i class="fas fa-save"></i>Simpan Barang Temuan</button>
          <button type="button" class="btn-ghost-sm" onclick="toggleForm()">Batal</button>
        </div>
      </form>
    </div>
 
    <!-- Search -->
    <form method="GET" class="mb-3 d-flex gap-2">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-input" placeholder="Cari barang temuan…" style="max-width:320px;padding:9px 14px;"/>
      <button type="submit" class="btn-accent" style="padding:9px 18px;"><i class="fas fa-search"></i></button>
      <?php if ($search): ?><a href="staff-barang.php" class="btn-ghost-sm">Reset</a><?php endif; ?>
    </form>
 
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title"><i class="fas fa-box-open me-2" style="color:#86efac;"></i>Daftar Barang Temuan <span style="color:#64748b;font-size:.8rem;font-weight:400;">(<?= count($items) ?> item)</span></span>
      </div>
      <?php if (empty($items)): ?>
        <div class="empty-state"><i class="fas fa-box-open"></i>Belum ada barang temuan.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="dash-table">
          <thead><tr>
            <th>#</th><th>Foto</th><th>Barang</th><th>Kategori</th><th>Lokasi</th><th>Tanggal</th><th>Tipe</th><th>Status</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            <?php foreach($items as $b): ?>
            <tr>
              <td style="color:#64748b;"><?= $b['id_barang'] ?></td>
              <td>
                <?php if (!empty($b['image'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($b['image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;"/>
                <?php else: ?>
                  <div style="width:40px;height:40px;background:rgba(255,255,255,.05);border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-image" style="color:#475569;font-size:.75rem;"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <div style="color:#fff;font-weight:600;"><?= htmlspecialchars($b['nama_barang']) ?></div>
                <div style="color:#64748b;font-size:.75rem;"><?= htmlspecialchars(substr($b['deskripsi']??'',0,40)) ?>…</div>
              </td>
              <td style="color:#94a3b8;"><?= htmlspecialchars($b['category']??'Other') ?></td>
              <td style="color:#94a3b8;font-size:.8rem;"><?= htmlspecialchars($b['lokasi_ditemukan']) ?></td>
              <td style="color:#94a3b8;font-size:.8rem;"><?= date('d M Y', strtotime($b['tanggal_ditemukan'])) ?></td>
              <td><span class="bdg bdg-<?= $b['type'] ?>"><?= strtoupper($b['type']) ?></span></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id_barang" value="<?= $b['id_barang'] ?>"/>
                  <input type="hidden" name="update_status" value="1"/>
                  <select name="status" onchange="this.form.submit()" class="form-input" style="padding:4px 10px;font-size:.75rem;width:auto;">
                    <option value="open"     <?= $b['status']==='open'?'selected':'' ?>>Open</option>
                    <option value="resolved" <?= $b['status']==='resolved'?'selected':'' ?>>Resolved</option>
                  </select>
                </form>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <a href="staff-pencocokan.php?action=add&id_barang=<?= $b['id_barang'] ?>" class="btn-ghost-sm" title="Cocokkan">
                    <i class="fas fa-link"></i>
                  </a>
                  <form method="POST" onsubmit="return confirm('Hapus barang ini? Data pencocokan terkait juga akan dihapus.')">
                    <input type="hidden" name="id_barang" value="<?= $b['id_barang'] ?>"/>
                    <input type="hidden" name="delete_barang" value="1"/>
                    <button type="submit" class="btn-ghost-sm btn-danger-sm">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
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
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleForm() {
  const f = document.getElementById('formBarang');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
function previewImg(input) {
  if (input.files && input.files[0]) {
    const img = document.getElementById('imgPreview');
    img.src = URL.createObjectURL(input.files[0]);
    img.style.display = 'block';
  }
}
</script>
</body>