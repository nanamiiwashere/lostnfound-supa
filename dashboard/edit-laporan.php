<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
require_once '../Core/supabase-handler.php';
require_once '../Core/supabase-img.php';
requireLogin();
$u = currentUser();
$activePage = 'laporan';
$success = isset($_GET['updated'])  ? 'Laporan anda berhasil diedit!' : '';
$error = '';

$id = (int)($_GET['id'] ?? 0);
if (!$id){
    header('Location: laporan.php');
    exit();
}

$stmt = $pdo -> prepare("SELECT * FROM laporan_kehilangan WHERE id_laporan=? AND id_pelapor=?");
$stmt -> execute ([$id, $u['id']]);
$item = $stmt -> fetch();

if (!$item){
    header('Location: laporan.php');
    exit();
}

if ($item['status'] !== 'open'){
    header("Location: detail-laporan.php?id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name     = trim($_POST['nama_barang'] ?? '');
    $desc     = trim($_POST['deskripsi'] ?? '');
    $location = trim($_POST['lokasi_kehilangan'] ?? '');
    $date     = trim($_POST['tanggal_kehilangan'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');

    if (!$name || !$location || !$date){
        $error = 'Nama barang, lokasi dan tanggal wajib diisi.';
    } else {
        $imageName = $item['image'];

        //handle for new image upload
        if (!empty($_FILES['image']['name'])){
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG atau WEBP.';
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 3MB.';
            } else {
              $fileName = uniqid('laporan_') . '.' . $ext;
                $supabaseUrl = uploadToSupabase(
                  $_FILES['image']['tmp_name'], $fileName, 'uploads'
                );

                if ($supabaseUrl !== false){
                  $imageName = $fileName;
                } else {
                  $error = 'Gagal upload gambar ke server!, try again';
                }
            }
          }

        if (isset($_POST['remove_image'])){
            $imageName = null;
        }

        if (!$error){
            $pdo -> prepare("UPDATE laporan_kehilangan SET nama_barang=?, deskripsi=?, lokasi_kehilangan=?, tanggal_kehilangan=?, category=?, image=? WHERE id_laporan=? AND id_pelapor=?") 
            -> execute([$name, $desc, $location, $date, $category, $imageName, $id, $u['id']]);
            header("Location: detail-laporan.php?id=$id&updated=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Laporan - LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
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
        <a href="detail-laporan.php?id=<?= $id ?>" style="color:#64748b;font-size:.82rem;text-decoration:none;"><i class="fas fa-arrow-left me-1"></i>Detail Laporan</a>
        <span style="color:#334155;margin:0 6px;">/</span>
        <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Edit Laporan</span>
      </div>
    </div>
  </div>
 
  <div class="page-content">
    <div class="row justify-content-center">
      <div class="col-lg-8">
 
        <?php if ($error): ?>
          <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
 
        <div class="dash-card p-4">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1.1rem;margin-bottom:1.5rem;">
            <i class="fas fa-edit me-2" style="color:#f97316;"></i>Edit Laporan Kehilangan
          </div>
 
          <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-8">
                <div class="form-group">
                  <label>Nama Barang <span style="color:#f97316;">*</span></label>
                  <input type="text" name="nama_barang" class="form-input"
                         value="<?= htmlspecialchars($_POST['nama_barang'] ?? $item['nama_barang']) ?>" required/>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Kategori</label>
                  <select name="category" class="form-input">
                    <?php foreach(['Electronics','Accessories','Bags','Keys','Documents','Pets','Jewelry','Other'] as $cat): ?>
                      <option value="<?= $cat ?>" <?= (($_POST['category'] ?? $item['category']) === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label>Deskripsi Barang</label>
                  <textarea name="deskripsi" class="form-input" rows="3"
                            placeholder="Warna, merk, ciri khusus..."><?= htmlspecialchars($_POST['deskripsi'] ?? $item['deskripsi'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label>Lokasi Kehilangan <span style="color:#f97316;">*</span></label>
                  <input type="text" name="lokasi_kehilangan" class="form-input"
                         value="<?= htmlspecialchars($_POST['lokasi_kehilangan'] ?? $item['lokasi_kehilangan']) ?>" required/>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Tanggal Kejadian <span style="color:#f97316;">*</span></label>
                  <input type="date" name="tanggal_kehilangan" class="form-input"
                         value="<?= $_POST['tanggal_kehilangan'] ?? date('Y-m-d', strtotime($item['tanggal_kehilangan'])) ?>" required/>
                </div>
              </div>
 
              
              <div class="col-12">
                <div class="form-group">
                  <label>Foto Bukti <span style="color:#64748b;font-weight:400;">(opsional, maks 3MB)</span></label>
 
                  <?php if (!empty($item['image'])): ?>
                  
                  <div id="existingImg" class="mb-3">
                    <div style="color:#94a3b8;font-size:.8rem;margin-bottom:8px;">Foto saat ini:</div>
                    <div class="d-flex align-items-center gap-3">
                      <img src="<?= htmlspecialchars(supabaseImageUrl($item['image'])) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:10px;"/>
                      <button type="button" onclick="removeExisting()" class="btn-ghost-sm" style="color:#f87171;border-color:rgba(239,68,68,.2);">
                        <i class="fas fa-trash"></i>Hapus Foto
                      </button>
                    </div>
                  </div>
                  <input type="hidden" name="remove_image" id="removeImageInput" value="0"/>
                  <?php endif; ?>
 
                  <div class="upload-area" id="uploadArea" onclick="document.getElementById('imageInput').click()" <?= !empty($item['image']) ? 'style="display:none;"' : '' ?>>
                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#64748b;display:block;"></i>
                    <div style="color:#64748b;font-size:.88rem;">Klik untuk upload foto baru</div>
                    <div style="color:#475569;font-size:.75rem;margin-top:4px;">JPG, PNG, WEBP</div>
                  </div>
                  <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;" onchange="previewImage(this)"/>
                  <img id="previewImg" style="max-width:100%;border-radius:10px;margin-top:1rem;display:none;" alt="preview"/>
                </div>
              </div>
            </div>
 
            <div class="d-flex gap-3 mt-3">
              <button type="submit" class="btn-accent"><i class="fas fa-save"></i>Simpan Perubahan</button>
              <a href="detail-laporan.php?id=<?= $id ?>" class="btn-ghost-sm" style="padding:9px 18px;">Batal</a>
            </div>
          </form>
        </div>
 
      </div>
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
  const img = document.getElementById('previewImg');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}
function removeExisting() {
  document.getElementById('existingImg').style.display  = 'none';
  document.getElementById('uploadArea').style.display   = 'block';
  document.getElementById('removeImageInput').value     = '1';
}
</script>
</body>
</html>