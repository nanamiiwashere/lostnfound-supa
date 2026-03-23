<?php 
require_once './connect.php';
require_once './Auth/auth3thparty.php';

$q = trim($_GET['q'] ?? '');
$catF = $_GET['category'] ?? 'all';
$where = ["type='found'", "status IN ('open','resolved')"];
$params = [];

if ($catF !== 'all'){
  $where[] = "category=?";
  $params[] = $catF;
}
if ($q !== ''){
  $where[] = "(nama_barang LIKE ? OR deskripsi LIKE ? OR lokasi_ditemukan LIKE ?)";
  $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo -> prepare("SELECT * FROM barang_temuan $whereSQL ORDER BY created_at DESC");
$stmt -> execute($params);
$items = $stmt -> fetchAll();
 
$catIcons = [
    'Electronics'=>'fa-mobile-alt','Accessories'=>'fa-wallet',
    'Pets'=>'fa-paw','Bags'=>'fa-shopping-bag','Keys'=>'fa-key',
    'Jewelry'=>'fa-gem','Documents'=>'fa-id-card','Other'=>'fa-box',
    'Clothing'=>'fa-tshirt'
];
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
  <link rel="stylesheet" href="./style.css"/>
</head>
<body>
 
<div id="nbar"></div>
 
<nav class="sticky top-0 z-50 border-b" style="background:rgba(8,12,20,.85);backdrop-filter:blur(16px);border-color:rgba(255,255,255,.07);" id="mainNav">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
    <a href="index.php" class="font-bold text-2xl text-white no-underline tracking-tight" style="font-family:'Clash Display',sans-serif;" onclick="startProgress()">
      Lostn<span style="color:#f97316;">Found</span>
    </a>
    <ul class="hidden lg:flex items-center gap-1 list-none m-0 p-0">
      <li><a href="index.php" class="nav-link"        onclick="startProgress()">Beranda</a></li>
      <li><a href="items.php" class="nav-link active"                          >Cari Barang</a></li>
      <li><a href="news.php"  class="nav-link"        onclick="startProgress()">Berita</a></li>
      <li><a href="about.php" class="nav-link"        onclick="startProgress()">Tentang</a></li>
    </ul>
    <div class="hidden lg:flex items-center gap-2">
      <?php if (isLoggedIn()): $u = currentUser(); ?>
        <a href="Dashboard/buat-laporan.php" class="btn-nav-accent" onclick="startProgress()">
          <i class="fas fa-plus me-1"></i>Lapor Kehilangan
        </a>
        <!-- Avatar dropdown -->
        <div class="relative" id="avatarWrap">
          <button onclick="toggleDropdown()" class="btn-avatar">
            <?php if (!empty($u['avatar'])): ?>
              <?php
                $av = $u['avatar'] ?? '';
                $avSrc = str_starts_with($av, 'http') ? $av : 'uploads/' . $av;?>
              <img src="<?= htmlspecialchars($avSrc) ?>" class="avatar-img" alt=""/>
            <?php else: ?>
              <div class="avatar-initial"><?= strtoupper(substr($u['name'],0,1)) ?></div>
            <?php endif; ?>
          </button>
          <div id="avatarDropdown" class="hidden absolute right-0 mt-2 w-52 rounded-2xl py-2 z-50"
               style="background:#1a2332;border:1px solid rgba(255,255,255,.07);box-shadow:0 20px 40px rgba(0,0,0,.5);">
            <div class="px-4 py-2">
              <div class="text-white text-sm font-semibold"><?= htmlspecialchars($u['name']) ?></div>
              <div class="text-xs mt-0.5" style="color:#64748b;">
                <?php $icons=['google'=>'fab fa-google','discord'=>'fab fa-discord','email'=>'fas fa-envelope']; ?>
                <i class="<?= $icons[$u['provider']]??'fas fa-user' ?> mr-1"></i><?= ucfirst($u['provider']) ?>
              </div>
            </div>
            <hr style="border-color:rgba(255,255,255,.08);margin:4px 0;"/>
            <a href="Dashboard/index.php"   class="dropdown-dark-item" onclick="startProgress()"><i class="fas fa-th-large mr-2"></i>Dashboard</a>
            <a href="Dashboard/laporan.php" class="dropdown-dark-item" onclick="startProgress()"><i class="fas fa-file-alt mr-2"></i>Laporan Saya</a>
            <a href="Dashboard/profil.php"  class="dropdown-dark-item" onclick="startProgress()"><i class="fas fa-user-cog mr-2"></i>Settings</a>
            <hr style="border-color:rgba(255,255,255,.08);margin:4px 0;"/>
            <a href="Auth/logout.php" class="dropdown-dark-item" style="color:#f87171;" onclick="startProgress()"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost"  onclick="startProgress()">Login</a>
        <a href="./register.php" class="btn-nav-accent" onclick="startProgress()">Register</a>
      <?php endif; ?>
    </div>
    <button onclick="toggleMobileMenu()" class="lg:hidden" style="background:none;border:none;color:#e2e8f0;cursor:pointer;padding:4px;">
      <i class="fas fa-bars text-lg" id="hamburgerIcon"></i>
    </button>
  </div>
  <div id="mobileMenu" class="nav-hidden lg:hidden border-t px-4 py-3" style="border-color:rgba(255,255,255,.07);background:rgba(8,12,20,.97);">
    <ul class="list-none m-0 p-0 flex flex-col gap-1 mb-3">
      <li><a href="index.php" class="nav-link block"        onclick="startProgress()">Beranda</a></li>
      <li><a href="items.php" class="nav-link active block"                          >Cari Barang</a></li>
      <li><a href="news.php"  class="nav-link block"        onclick="startProgress()">Berita</a></li>
      <li><a href="about.php" class="nav-link block"        onclick="startProgress()">Tentang</a></li>
    </ul>
    <div class="flex flex-col gap-2 pt-2" style="border-top:1px solid rgba(255,255,255,.07);">
      <?php if (isLoggedIn()): ?>
        <a href="Dashboard/index.php" class="btn-nav-accent text-center" onclick="startProgress()">Dashboard</a>
        <a href="Auth/logout.php"     class="btn-nav-ghost text-center" style="color:#f87171;" onclick="startProgress()">Logout</a>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost text-center"  onclick="startProgress()">Login</a>
        <a href="./register.php" class="btn-nav-accent text-center" onclick="startProgress()">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
 
<!-- PAGE HEADER -->
<section style="background:radial-gradient(ellipse 80% 60% at 60% 40%,rgba(249,115,22,.08) 0%,transparent 60%),var(--bg);padding:3.5rem 0 2.5rem;border-bottom:1px solid var(--border);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.025) 1px,transparent 1px);background-size:40px 40px;"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
          <i class="fas fa-box-open me-2"></i>Barang Ditemukan Petugas
        </p>
        <h1 style="font-family:'Clash Display',sans-serif;font-size:clamp(2rem,4vw,2.8rem);font-weight:700;color:#fff;margin-bottom:12px;letter-spacing:-.02em;">
          Cari <span style="color:var(--accent);">Barang Kamu</span>
        </h1>
        <p style="color:var(--muted);font-size:.95rem;margin-bottom:1.5rem;max-width:440px;">
          Daftar barang yang ditemukan oleh petugas KRL Commuterline Nusantara.
        </p>
        <div class="items-search">
          <input type="text" id="searchInput" placeholder="Cari nama barang, lokasi, kategori..." oninput="applyFilters()"/>
          <button onclick="applyFilters()"><i class="fas fa-search"></i></button>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
          <?php
          $totalAll      = count($items);
          $totalOpen     = count(array_filter($items, fn($i)=>$i['status']==='open'));
          $totalResolved = count(array_filter($items, fn($i)=>$i['status']==='resolved'));
          foreach([
            ['num'=>$totalAll,      'lbl'=>'Total',       'color'=>'#22c55e'],
            ['num'=>$totalOpen,     'lbl'=>'FOUND',    'color'=>'#f97316'],
            ['num'=>$totalResolved, 'lbl'=>'RETURNED','color'=>'#818cf8'],
          ] as $s): ?>
          <div class="p-3 rounded-3 text-center" style="background:var(--card);border:1px solid var(--border);min-width:90px;">
            <div style="font-family:'Clash Display',sans-serif;font-size:1.8rem;font-weight:700;color:<?= $s['color'] ?>;"><?= $s['num'] ?></div>
            <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:.05em;"><?= $s['lbl'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- ITEMS -->
<section class="py-5">
  <div class="container">
 
    <!-- Filter tabs -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        <button class="filter-tab active" onclick="filterByStatus('all',this)">Semua</button>
        <button class="filter-tab" onclick="filterByStatus('open',this)">
          <span style="background:#22c55e;width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px;"></span>Ketemu
        </button>
        <button class="filter-tab" onclick="filterByStatus('resolved',this)">
          <span style="background:#818cf8;width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px;"></span>Dikembalikan
        </button>
      </div>
      <span class="count-badge" id="itemCount"><?= $totalAll ?> barang</span>
    </div>
 
    <?php if (empty($items)): ?>
      <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <div style="font-size:1rem;font-weight:600;margin-bottom:8px;">Belum ada barang temuan</div>
        <div style="font-size:.85rem;">Petugas belum menginput barang temuan.</div>
      </div>
    <?php else: ?>
 
      <div class="row g-4" id="itemsGrid">
        <?php foreach($items as $i => $item):
          $icon = $catIcons[$item['category']??'Other'] ?? 'fa-box';
        ?>
        <div class="col-sm-6 col-lg-3 item-col"
             data-status="<?= $item['status'] ?>"
             data-name="<?= strtolower(htmlspecialchars($item['nama_barang'])) ?>"
             data-location="<?= strtolower(htmlspecialchars($item['lokasi_ditemukan'])) ?>"
             data-category="<?= strtolower($item['category']??'') ?>"
             style="animation-delay:<?= min($i * .04, .4) ?>s;">
          <div class="item-card h-100" onclick="location.href='./dashboard/item-detail.php?id=<?= $item['id_barang'] ?>'">
            <div class="item-card-img-wrap">
              <?php if (!empty($item['image'])): ?>
                <img src="./uploads/<?= htmlspecialchars($item['image']) ?>" class="item-card-img" alt=""/>
              <?php else: ?>
                <div class="img-placeholder">
                  <i class="fas <?= $icon ?> fa-2x" style="color:rgba(255,255,255,.15);"></i>
                </div>
              <?php endif; ?>
              <?php if ($item['status']==='open'): ?>
                <span class="type-badge found">FOUND</span>
              <?php elseif ($item['status']==='resolved'): ?>
                <span class="type-badge" style="background:rgba(129,140,248,.9);">RETURNED</span>
              <?php else: ?>
                <span class="type-badge" style="background:rgba(100,116,139,.9);"><?= strtoupper($item['status']) ?></span>
              <?php endif; ?>
            </div>
            <div class="item-card-body">
              <div class="item-cat"><?= htmlspecialchars($item['category']??'Other') ?></div>
              <div class="item-title"><?= htmlspecialchars($item['nama_barang']) ?></div>
              <div class="item-loc mb-1">
                <i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($item['lokasi_ditemukan']) ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);margin-bottom:12px;">
                <i class="fas fa-calendar me-1"></i><?= date('d M Y', strtotime($item['created_at'])) ?>
              </div>
              <?php if (isLoggedIn()): ?>
                <a href="./dashboard/item-detail.php?id=<?= $item['id_barang'] ?>"
                   class="btn btn-sm w-100 fw-600"
                   style="background:rgba(249,115,22,.15);color:var(--accent);border-radius:8px;border:1px solid rgba(249,115,22,.2);"
                   onclick="event.stopPropagation()">
                  Lihat Detail
                </a>
              <?php else: ?>
                <div class="guest-gate">
                  <a href="./login.php" onclick="event.stopPropagation()">Login</a>
                  atau
                  <a href="./register.php" onclick="event.stopPropagation()">Register</a>
                  untuk lihat detail
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
 
      <div id="noResults" style="display:none;" class="empty-state">
        <i class="fas fa-search"></i>
        <div style="font-size:1rem;font-weight:600;margin-bottom:8px;">Barang tidak ditemukan</div>
        <div style="font-size:.85rem;">Coba kata kunci yang berbeda.</div>
      </div>
 
    <?php endif; ?>
  </div>
</section>
 
<!-- CTA GUEST -->
<?php if (!isLoggedIn()): ?>
<section class="py-5" style="background:var(--surface);border-top:1px solid var(--border);">
  <div class="container">
    <div class="cta-banner">
      <div class="mb-3" style="font-size:2.5rem;"><i class="fas fa-file-alt" style="color:var(--accent);"></i></div>
      <h2 class="section-title mb-3">Barang kamu hilang?</h2>
      <p style="color:var(--muted);max-width:480px;margin:0 auto 1.5rem;">
        Buat laporan agar petugas bisa mencocokkan dengan barang temuan yang ada.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="./register.php" class="btn-primary-custom" onclick="startProgress()"><i class="fas fa-user-plus"></i>Buat Akun Gratis</a>
        <a href="./login.php"    class="btn-ghost-custom"   onclick="startProgress()"><i class="fas fa-sign-in-alt"></i>Sudah punya akun? Login</a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
 
<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-4 mb-3 mb-md-0">
        <div class="navbar-brand mb-1">Lostn<span class="accent">Found</span></div>
        <div style="color:var(--muted);font-size:.85rem;">Forum penemuan barang hilang KRL Commuterline</div>
      </div>
      <div class="col-md-4 text-center mb-3 mb-md-0">
        <div class="d-flex justify-content-center gap-3">
          <a href="items.php" style="color:var(--accent);font-size:.85rem;text-decoration:none;font-weight:600;">Cari Barang</a>
          <a href="news.php"  style="color:var(--muted);font-size:.85rem;text-decoration:none;">Berita</a>
          <a href="about.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;">Tentang</a>
        </div>
      </div>
      <div class="col-md-4 text-md-end">
        <div style="color:var(--muted);font-size:.85rem;">© <?= date('Y') ?> LostnFound</div>
      </div>
    </div>
  </div>
</footer>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentStatus = 'all';
 
function filterByStatus(status, btn) {
  currentStatus = status;
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
 
function applyFilters() {
  const q     = (document.getElementById('searchInput')?.value || '').toLowerCase();
  const cols  = document.querySelectorAll('.item-col');
  let visible = 0;
 
  cols.forEach(col => {
    const matchSearch = !q ||
      col.dataset.name.includes(q) ||
      col.dataset.location.includes(q) ||
      col.dataset.category.includes(q);
    const matchStatus = currentStatus === 'all' || col.dataset.status === currentStatus;
 
    if (matchSearch && matchStatus) { col.style.display = ''; visible++; }
    else col.style.display = 'none';
  });
 
  document.getElementById('itemCount').textContent = visible + ' barang';
  document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}
 
function toggleDropdown() {
  const dd = document.getElementById('avatarDropdown');
  if (!dd) return;
  dd.classList.toggle('hidden');
  if (!dd.classList.contains('hidden')) {
    dd.style.opacity='0'; dd.style.transform='translateY(-8px)';
    dd.style.transition='opacity .18s, transform .18s';
    requestAnimationFrame(()=>{ dd.style.opacity='1'; dd.style.transform='none'; });
  }
}
document.addEventListener('click', e => {
  const w = document.getElementById('avatarWrap');
  if (w && !w.contains(e.target)) document.getElementById('avatarDropdown')?.classList.add('hidden');
});
 
function toggleMobileMenu() {
  const m = document.getElementById('mobileMenu');
  const i = document.getElementById('hamburgerIcon');
  const h = m.classList.contains('nav-hidden');
  m.classList.toggle('nav-hidden', !h);
  m.classList.toggle('nav-visible', h);
  i.className = h ? 'fas fa-times text-lg' : 'fas fa-bars text-lg';
}
 
function startProgress() {
  const bar = document.getElementById('nbar');
  bar.style.width='0'; bar.style.opacity='1';
  let w=0;
  const iv = setInterval(()=>{ w=Math.min(w+Math.random()*15,85); bar.style.width=w+'%'; },150);
  window.addEventListener('beforeunload',()=>{ clearInterval(iv); bar.style.width='100%'; setTimeout(()=>bar.style.opacity='0',200); });
}
</script>
</body>
</html>