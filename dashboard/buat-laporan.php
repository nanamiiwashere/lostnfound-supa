<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
require_once '../Core/supabase-handler.php';
require_once '../Core/supabase-img.php';
requireLogin();
$u = currentUser();
$activePage = 'buat';
$success = isset($_GET['added'])  ? 'Laporan berhasil dibuat!' : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['nama_barang'] ?? '');
    $description = trim($_POST['deskripsi'] ?? '');
    $location = trim($_POST['lokasi_kehilangan'] ?? '');
    $date = trim($_POST['tanggal_kehilangan'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');

    if (!$name || !$location || !$date){
        $error = 'Nama barang, lokasi dan tanggal wajib diisi.';
    } else {
        $imageName = null;
        if (!empty($_FILES['image']['name'])){
            $ext = strtolower(pathinfo($_FILES['image'] ['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)){
                $error = 'Format foto tidak di dukung. Gunakan format JPG, PNG atau WEBP!';
            } elseif ($_FILES['image'] ['size'] > 3*1024*1024){
                $error = 'File upload failed. Maximum allowed size is 3 MB';
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


        if (!$error){
            $pdo -> prepare("INSERT INTO laporan_kehilangan (id_pelapor, nama_barang, deskripsi, lokasi_kehilangan, tanggal_kehilangan, category, image, status, type)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'lost')") -> execute([$u['id'], $name, $description, $location, $date, $category, $imageName]);

            header("Location: buat-laporan.php?added=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Buat Laporan - LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../dashboard/partials/style.css"/>
  <style>
    .upload-area { border:2px dashed rgba(255,255,255,.12); border-radius:12px; padding:2rem; text-align:center; cursor:pointer; transition:border-color .2s; }
    .upload-area:hover { border-color:#f97316; }
    .preview-img { max-width:100%; border-radius:10px; margin-top:1rem; display:none; }
  </style>
</head>
<body>
 
<?php require_once 'partials/sidebar.php'; ?>
 
<div class="main-wrap">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="d-lg-none" style="background:none;border:none;color:#e2e8f0;font-size:1.1rem;padding:0;cursor:pointer;"><i class="fas fa-bars"></i></button>
      <div>
        <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Buat Laporan</span>
        <span style="color:#64748b;font-size:.82rem;margin-left:8px;">Laporan kehilangan barang</span>
      </div>
    </div>
  </div>
 
  <div class="page-content">
    <div class="row justify-content-center">
      <div class="col-lg-8">
 
        <?php if ($error):   ?><div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?> <a href="laporan.php" style="color:#86efac;font-weight:700;">Lihat laporan saya →</a></div>
        <?php endif; ?>
 
        <div class="dash-card p-4">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1.1rem;margin-bottom:1.5rem;">
            <i class="fas fa-file-alt me-2" style="color:#f97316;"></i>Detail Laporan Kehilangan
          </div>
 
          <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-8">
                <div class="form-group">
                  <label>Nama Barang <span style="color:#f97316;">*</span></label>
                  <input type="text" name="nama_barang" class="form-input" placeholder="cth: Dompet kulit hitam" value="<?= htmlspecialchars($_POST['nama_barang']??'') ?>" required/>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Kategori</label>
                  <select name="category" class="form-input">
                    <?php foreach(['Electronics','Accessories','Bags', 'Clothing','Keys','Documents','Pets','Jewelry','Other'] as $cat): ?>
                      <option value="<?= $cat ?>" <?= ($_POST['category']??'')===$cat?'selected':'' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label>Deskripsi Barang</label>
                  <textarea name="deskripsi" class="form-input" rows="3" placeholder="Deskripsikan barang secara detail: warna, merk, ciri khusus, isi dompet, dll..."><?= htmlspecialchars($_POST['deskripsi']??'') ?></textarea>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label>Lokasi Kehilangan <span style="color:#f97316;">*</span></label>
                  <input type="text" name="lokasi_kehilangan" class="form-input" placeholder="cth: Stasiun Tanah Abang, Gerbong 3" value="<?= htmlspecialchars($_POST['lokasi_kehilangan']??'') ?>" required/>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Tanggal Kejadian <span style="color:#f97316;">*</span></label>
                  <input type="date" name="tanggal_kehilangan" class="form-input" value="<?= $_POST['tanggal_kehilangan']??date('Y-m-d') ?>" required/>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label>Foto Bukti <span style="color:#64748b;font-weight:400;">(opsional, maks 3MB)</span></label>
                  <div class="upload-area" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#64748b;display:block;"></i>
                    <div style="color:#64748b;font-size:.88rem;">Klik untuk upload foto</div>
                    <div style="color:#475569;font-size:.75rem;margin-top:4px;">JPG, PNG, WEBP</div>
                  </div>
                  <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;" onchange="previewImage(this)"/>
                  <img id="previewImg" class="preview-img" alt="preview"/>
                </div>
              </div>
            </div>
 
            <div class="d-flex gap-3 mt-3">
              <button type="submit" class="btn-accent"><i class="fas fa-paper-plane"></i>Kirim Laporan</button>
              <a href="laporan.php" class="btn-ghost-sm" style="padding:9px 18px;">Batal</a>
            </div>
          </form>
        </div>
 
        <!-- Info box -->
        <div class="mt-4 p-4 rounded-3" style="background:rgba(249,115,22,.06);border:1px solid rgba(249,115,22,.15);">
          <div style="color:#fb923c;font-weight:700;font-size:.88rem;margin-bottom:8px;"><i class="fas fa-info-circle me-2"></i>Tips membuat laporan yang baik</div>
          <ul style="color:#64748b;font-size:.82rem;margin:0;padding-left:1.2rem;line-height:1.8;">
            <li>Deskripsikan barang sedetail mungkin (warna, merk, ukuran, ciri khusus)</li>
            <li>Sebutkan lokasi secara spesifik (nama stasiun, nomor gerbong, jam kejadian)</li>
            <li>Upload foto jika ada untuk mempercepat proses pencarian</li>
            <li>Petugas akan menghubungi kamu jika ada barang yang cocok</li>
          </ul>
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
</script>
</body>
</html>