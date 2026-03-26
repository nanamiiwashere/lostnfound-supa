<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';
requireLogin();
$u = currentUser();
$activePage = 'profile';
$error = $success = '';

$stmt = $pdo -> prepare("SELECT * FROM users WHERE id_user=?");
$stmt -> execute([$u['id']]);
$user = $stmt -> fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';

    if ($action === 'profile_update'){
        $nama = trim($_POST['nama'] ?? '');
        if (!$nama){
            $error = 'Nama tidak boleh kosong.';
        } else {
            $avatarName = $user['avatar'] ?? null;


    if (!empty($_FILES['avatar']['name'])){
      $ext  = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','gif','webp'];
      if (!in_array($ext, $allowed)){
        $error = 'Format foto tidak didukung.';
      } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024){
        $error = 'Ukuran foto maksimal 2 MB.';
      } else {
          $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
          $avatarName = 'avatars/avatar_'. $u['id'] . '_' . time() . '.' . $ext;
          $uploadDir = '../uploads/avatars';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
          move_uploaded_file($_FILES['avatar']['tmp_name'], '../uploads/' . $avatarName);
      }
    }

    if (!$error){
      $pdo -> prepare("UPDATE users SET nama=?, avatar=? WHERE id_user=?") -> execute ([$nama, $avatarName, $u['id']]);
      $_SESSION['name'] = $nama;
      $_SESSION['avatar'] = $avatarName ?: null;
      
      $success = 'Profil berhasil diupdate!';
      $user['nama'] = $nama;
      $user['avatar'] = $avatarName;
    }
  }
}  


    if($action === 'change_password'){
        $oldPw = $_POST['old_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$user['password']){
            $error = 'Akun OAuth tidak bisa ganti password.';
        } elseif (!password_verify($oldPw, $user['password'])){
            $error = 'Password lama salah.';
        } elseif (strlen($newPw) < 8){
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($newPw !== $confirm){
            $error = 'Konfirmasi password tidak cocok.';
        } else{
            $pdo -> prepare("UPDATE users SET password=? WHERE id_user=?") -> execute([password_hash($newPw, PASSWORD_BCRYPT), $u['id']]);
            $success = 'Password berhasil diubah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil — LostnFound</title>
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
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Profil Saya</span>
    </div>
  </div>
 
  <div class="page-content">
    <?php if ($error):   ?><div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
 
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="dash-card p-4 text-center">
          <?php
            $avatarSrc = null;
            if (!empty($user['avatar'])) {
                // OAuth avatar = full URL, local upload = relative path
                $avatarSrc = str_starts_with($user['avatar'], 'http') ? $user['avatar'] : '../uploads/' . $user['avatar'];
            }
          ?>
          <?php if ($avatarSrc): ?>
            <img src="<?= htmlspecialchars($avatarSrc) ?>" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;" alt=""/>
          <?php else: ?>
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;background:#f97316;font-family:'Clash Display',sans-serif;font-size:2rem;font-weight:700;color:#fff;">
              <?= strtoupper(substr($user['nama']??'U',0,1)) ?>
            </div>
          <?php endif; ?>
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1.1rem;"><?= htmlspecialchars($user['nama']) ?></div>
          <div style="color:#64748b;font-size:.85rem;margin-top:4px;"><?= htmlspecialchars($user['email']) ?></div>
          <div class="mt-2">
            <?php $pIcons=['google'=>'fab fa-google','discord'=>'fab fa-discord','email'=>'fas fa-envelope']; ?>
            <span class="bdg" style="background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.08);">
              <i class="<?= $pIcons[$user['oauth_provider']??'email']??'fas fa-envelope' ?> me-1"></i>
              <?= ucfirst($user['oauth_provider']??'email') ?>
            </span>
          </div>
          <div class="mt-2">
            <span class="bdg bdg-<?= $user['role']==='admin'?'found':'open' ?>"><?= ucfirst($user['role']??'user') ?></span>
          </div>
          <div style="color:#475569;font-size:.75rem;margin-top:12px;">Bergabung <?= date('d M Y', strtotime($user['created_at'])) ?></div>
        </div>
      </div>
 
      <div class="col-lg-8">
        <div class="dash-card p-4 mb-4">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;"><i class="fas fa-user-edit me-2" style="color:#f97316;"></i>Edit Profil</div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="profile_update"/>
            <div class="form-group">
              <label>Foto Profil</label>
              <div class="d-flex align-items-center gap-3">
                <?php
                  $avatarSrc2 = null;
                  if (!empty($user['avatar'])) {
                      $avatarSrc2 = str_starts_with($user['avatar'], 'http') ? $user['avatar'] : '../uploads/' . $user['avatar'];
                  }
                ?>
                <?php if ($avatarSrc2): ?>
                  <img src="<?= htmlspecialchars($avatarSrc2) ?>" id="avatarPreview" class="rounded-circle" style="width:56px;height:56px;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div id="avatarPreview" class="rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:#f97316;font-size:1.3rem;font-weight:700;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($user['nama']??'U',0,1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <label for="avatarInput" class="btn-ghost-sm" style="cursor:pointer;margin:0;"><i class="fas fa-camera me-1"></i>Ganti Foto</label>
                  <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)"/>
                  <div style="color:#475569;font-size:.72rem;margin-top:4px;">JPG, PNG, WebP · Maks 2MB</div>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" class="form-input" value="<?= htmlspecialchars($user['nama']) ?>" required/>
            </div>
            <div class="form-group">
              <label>Email <span style="color:#475569;font-weight:400;">(tidak bisa diubah)</span></label>
              <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.5;cursor:not-allowed;"/>
            </div>
            <button type="submit" class="btn-accent">Simpan Perubahan</button>
          </form>
        </div>
 
        <!-- Password section -->
        <?php if ($user['oauth_provider']==='email' && $user['password']): ?>
        <div class="dash-card p-4">
          <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;"><i class="fas fa-lock me-2" style="color:#f97316;"></i>Ganti Password</div>
          <form method="POST">
            <input type="hidden" name="action" value="change_password"/>
            <div class="form-group">
              <label>Password Lama</label>
              <input type="password" name="old_password" class="form-input" placeholder="••••••••" required/>
            </div>
            <div class="form-group">
              <label>Password Baru</label>
              <input type="password" name="new_password" class="form-input" placeholder="Min. 8 karakter" required/>
            </div>
            <div class="form-group">
              <label>Konfirmasi Password Baru</label>
              <input type="password" name="confirm_password" class="form-input" placeholder="Ulangi password baru" required/>
            </div>
            <button type="submit" class="btn-accent" style="background:rgba(129,140,248,.15);color:#818cf8;border:1px solid rgba(129,140,248,.2);">Ganti Password</button>
          </form>
        </div>
        <?php else: ?>
        <div class="dash-card p-4" style="background:rgba(255,255,255,.02);">
          <div style="color:#64748b;font-size:.85rem;text-align:center;">
            <i class="fas fa-info-circle me-1"></i>
            Akun ini login via <?= ucfirst($user['oauth_provider']??'OAuth') ?>, tidak perlu ganti password.
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('avatarPreview');
      prev.outerHTML = '<img src="' + e.target.result + '" id="avatarPreview" class="rounded-circle" style="width:56px;height:56px;object-fit:cover;flex-shrink:0;"/>';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>