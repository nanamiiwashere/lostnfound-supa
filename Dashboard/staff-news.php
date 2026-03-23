<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';

requireLogin();
if(($_SESSION['role'] ?? '') !== 'staff'){
    header('Location: index.php');
    exit();
}

$u = currentUser();
$activePage = 's-news';

$success = isset($_GET['added'])  ? 'Berita berhasil dipublish!' :
             (isset($_GET['deleted']) ? 'Berita berhasil dihapus.'   : '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])){
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    if ($title && $body){
        $pdo -> prepare("INSERT INTO news (title, body, author_id, created_at) VALUES (?, ?, ?, NOW())")
        -> execute ([$title, $body, $u['id']]);
        header("Location: staff-news.php?added=1");
        exit();
    } else {
        $error = 'Judul dan isi berita tidak boleh kosong.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])){
    $id = (int)$_POST['id_news'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    if ($title && $body){
        $pdo -> prepare("UPDATE news SET title=?, body=? WHERE id_news=?") -> execute ([$title, $body, $id]);
        $success = 'Berita berhasil diupdate.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_news'])){
    $id = (int)$_POST['id_news'];
    if ($id){ 
        $pdo -> prepare("DELETE FROM news WHERE id_news=?") -> execute ([$id]);
        header("Location: staff-news.php?deleted=1");
        exit();
    }
}

$allNews = $pdo->query("SELECT n.*, u.nama AS nama_author FROM news n LEFT JOIN users u ON n.author_id=u.id_user ORDER BY n.created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Berita — Staff</title>
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
      <span style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;">Manajemen Berita</span>
    </div>
    <button onclick="toggleForm()" class="btn-accent" style="background:rgba(249,115,22,.12);color:#fb923c;border:1px solid rgba(249,115,22,.2);"><i class="fas fa-plus"></i>Tulis Berita</button>
  </div>
 
  <div class="page-content">
    <?php if ($success): ?>
      <div class="alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-error mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>
 
    <div id="formNews" class="dash-card p-4 mb-4" style="<?= isset($_GET['add'])?'':'display:none;' ?>">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;">
        <i class="fas fa-newspaper me-2" style="color:#fb923c;"></i>Tulis Berita Baru
      </div>
      <form method="POST">
        <input type="hidden" name="add_news" value="1"/>
        <div class="form-group">
          <label>Judul Berita <span style="color:#f87171;">*</span></label>
          <input type="text" name="title" class="form-input" placeholder="Judul berita yang menarik…" required/>
        </div>
        <div class="form-group">
          <label>Isi Berita <span style="color:#f87171;">*</span></label>
          <textarea name="body" class="form-input" rows="6" placeholder="Tulis isi berita di sini…" required></textarea>
        </div>
        <div class="d-flex gap-2 mt-2">
          <button type="submit" class="btn-accent"><i class="fas fa-paper-plane"></i>Terbitkan</button>
          <button type="button" class="btn-ghost-sm" onclick="toggleForm()">Batal</button>
        </div>
      </form>
    </div>
 
    <?php if (empty($allNews)): ?>
      <div class="dash-card">
        <div class="empty-state"><i class="fas fa-newspaper"></i>Belum ada berita. Mulai tulis berita pertama!</div>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach($allNews as $n): ?>
        <div class="col-12">
          <div class="dash-card p-4">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div style="flex:1;min-width:0;">
                <h5 style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin:0 0 6px;"><?= htmlspecialchars($n['title']) ?></h5>
                <div style="color:#64748b;font-size:.78rem;margin-bottom:10px;">
                  <i class="fas fa-user me-1"></i><?= htmlspecialchars($n['nama_author']??'Staff') ?>
                  <span class="mx-2">·</span>
                  <i class="fas fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                </div>
                <p style="color:#94a3b8;font-size:.88rem;line-height:1.7;margin:0;" id="body_preview_<?= $n['id_news'] ?>">
                  <?= htmlspecialchars(substr($n['body'],0,180)) ?><?= strlen($n['body'])>180?'…':'' ?>
                </p>
              </div>
              <div class="d-flex gap-2 flex-shrink-0">
                <button onclick="openEditModal(<?= $n['id_news'] ?>, `<?= addslashes(htmlspecialchars($n['title'])) ?>`, `<?= addslashes(htmlspecialchars($n['body'])) ?>`)" class="btn-ghost-sm">
                  <i class="fas fa-edit"></i>Edit
                </button>
                <form method="POST" onsubmit="return confirm('Hapus berita ini?')">
                  <input type="hidden" name="id_news" value="<?= $n['id_news'] ?>"/>
                  <button type="submit" name="delete_news" value="1" class="btn-ghost-sm" style="color:#f87171;border-color:rgba(239,68,68,.2);">
                    <i class="fas fa-trash"></i>Hapus
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
 
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center;padding:1rem;">
  <div class="dash-card p-4" style="width:100%;max-width:560px;">
    <div style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;margin-bottom:1.2rem;">
      <i class="fas fa-edit me-2" style="color:#fb923c;"></i>Edit Berita
    </div>
    <form method="POST">
      <input type="hidden" name="edit_news" value="1"/>
      <input type="hidden" name="id_news" id="edit_id"/>
      <div class="form-group">
        <label>Judul</label>
        <input type="text" name="title" id="edit_title" class="form-input" required/>
      </div>
      <div class="form-group">
        <label>Isi</label>
        <textarea name="body" id="edit_body" class="form-input" rows="6" required></textarea>
      </div>
      <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn-accent"><i class="fas fa-save"></i>Simpan</button>
        <button type="button" class="btn-ghost-sm" onclick="closeModal()">Batal</button>
      </div>
    </form>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleForm() {
  const f = document.getElementById('formNews');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
  if (f.style.display !== 'none') f.scrollIntoView({behavior:'smooth'});
}
function openEditModal(id, title, body) {
  document.getElementById('edit_id').value    = id;
  document.getElementById('edit_title').value = title;
  document.getElementById('edit_body').value  = body;
  const m = document.getElementById('editModal');
  m.style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
document.getElementById('editModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
 