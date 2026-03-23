<?php
require_once './connect.php';
require_once './auth/auth3thparty.php';

$news = $pdo -> query("SELECT * FROM news ORDER BY created_at DESC") -> fetchAll();

$demoNews = [
    ['id_news'=>1, 'title'=>'Titik Drop-off Baru di Stasiun Gambir', 'body'=>'Kami telah membuka titik drop-off baru untuk barang temuan di Stasiun Gambir, tersedia setiap hari kerja pukul 08.00–17.00 WIB. Tim petugas kami siap membantu proses pengembalian barang ke pemiliknya.', 'author_id'=>null, 'created_at'=>'2026-03-10'],
    ['id_news'=>2, 'title'=>'Februari: 47 Barang Berhasil Dikembalikan!', 'body'=>'Berkat kerjasama komunitas pengguna KRL dan tim petugas kami, sebanyak 47 barang hilang berhasil dikembalikan kepada pemiliknya di bulan Februari 2026. Terima kasih atas kepercayaan Anda!', 'author_id'=>null, 'created_at'=>'2026-03-05'],
    ['id_news'=>3, 'title'=>'Tips Membuat Laporan yang Efektif', 'body'=>'Laporan dengan deskripsi lengkap dan foto bukti memiliki tingkat keberhasilan 3x lebih tinggi. Pastikan Anda menyertakan detail seperti warna, merk, dan ciri khusus barang yang hilang.', 'author_id'=>null, 'created_at'=>'2026-02-28'],
    ['id_news'=>4, 'title'=>'Layanan LostnFound Kini Hadir di 12 Stasiun', 'body'=>'Kami dengan bangga mengumumkan perluasan layanan LostnFound ke 12 stasiun KRL Commuterline Nusantara. Setiap stasiun kini memiliki petugas khusus yang menangani laporan barang hilang.', 'author_id'=>null, 'created_at'=>'2026-02-20'],
    ['id_news'=>5, 'title'=>'Cara Klaim Barang Temuan', 'body'=>'Proses klaim barang temuan kini lebih mudah. Cukup buat laporan di aplikasi, unggah foto bukti kepemilikan, dan petugas kami akan menghubungi Anda dalam 1x24 jam untuk verifikasi.', 'author_id'=>null, 'created_at'=>'2026-02-15'],
    ['id_news'=>6, 'title'=>'Update Sistem: Notifikasi Real-time', 'body'=>'Kami telah memperbarui sistem notifikasi. Pengguna kini akan mendapatkan update real-time melalui email ketika ada barang temuan yang cocok dengan laporan kehilangan mereka.', 'author_id'=>null, 'created_at'=>'2026-02-10'],
];
$displayNews = empty($news) ? $demoNews : $news;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Berita — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="./style.css"/>
  </head>
<body>

<?php 
    $navItems = [
        ['href' => 'index.php', 'label' => 'Beranda'],
        ['href' => 'items.php', 'label' => 'Cari Barang'],
        ['href' => 'news.php', 'label' => 'Berita'],
        ['href' => 'about.php', 'label' => 'Tentang'],
    ]
?>

<nav class="sticky top-0 z-50 border-b" style="background:rgba(8,12,20,.85);backdrop-filter:blur(16px);border-color:rgba(255,255,255,.07);" id="mainNav">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
    <a href="index.php" class="font-bold text-2xl text-white no-underline tracking-tight" style="font-family:'Clash Display',sans-serif;">
      Lostn<span style="color:#f97316;">Found</span>
    </a>
    <ul class="hidden lg:flex items-center gap-1 list-none m-0 p-0">

    <?php foreach($navItems as $n): ?>
        <li><a href="<?= $n['href'] ?>" class="nav-link <?= isset($n['active'])?'active':'' ?>"><?= $n['label'] ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="hidden lg:flex items-center gap-2">
      <?php if (isLoggedIn()): $u = currentUser(); ?>
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
            <a href="Dashboard/index.php"  class="dropdown-dark-item"><i class="fas fa-th-large mr-2"></i>Dashboard</a>
            <a href="Dashboard/laporan.php"class="dropdown-dark-item"><i class="fas fa-file-alt mr-2"></i>History</a>
            <a href="Dashboard/profil.php" class="dropdown-dark-item"><i class="fas fa-user-circle mr-2"></i>Profile</a>
            <hr style="border-color:rgba(255,255,255,.08);margin:4px 0;"/>
            <a href="Auth/logout.php" class="dropdown-dark-item" style="color:#f87171;"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost">Login</a>
        <a href="./register.php" class="btn-nav-accent">Register</a>
      <?php endif; ?>
    </div>
    <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-300 p-2" style="background:none;border:none;cursor:pointer;">
      <i class="fas fa-bars text-lg" id="hamburgerIcon"></i>
    </button>
  </div>
  <div id="mobileMenu" class="nav-hidden lg:hidden border-t px-4 py-3" style="border-color:rgba(255,255,255,.07);background:rgba(8,12,20,.97);">
    <ul class="list-none m-0 p-0 flex flex-col gap-1 mb-3">
      <?php foreach($navItems as $n): ?>
        <li><a href="<?= $n['href'] ?>" class="nav-link <?= isset($n['active'])?'active':'' ?> block"><?= $n['label'] ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="flex flex-col gap-2 pt-2" style="border-top:1px solid rgba(255,255,255,.07);">
      <?php if (isLoggedIn()): ?>
        <a href="Dashboard/index.php" class="btn-nav-accent text-center">Dashboard</a>
        <a href="Auth/logout.php"     class="btn-nav-ghost text-center" style="color:#f87171;">Logout</a>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost text-center">Login</a>
        <a href="./register.php" class="btn-nav-accent text-center">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
 
<div class="news-hero">
  <div class="container">
    <div style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
      <i class="fas fa-newspaper me-2"></i>Updates & Informasi
    </div>
    <h1 style="font-family:'Clash Display',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:700;color:#fff;margin-bottom:12px;letter-spacing:-.02em;">
      Berita <span style="color:var(--accent);">Terbaru</span>
    </h1>
    <p style="color:var(--muted);font-size:1rem;max-width:500px;margin:0;">
      Informasi terkini seputar layanan LostnFound dan KRL Commuterline Nusantara.
    </p>
  </div>
</div>
 

<section class="py-5">
  <div class="container">
    <?php if (empty($displayNews)): ?>
      <div class="text-center py-5" style="color:var(--muted);">
        <i class="fas fa-newspaper fa-3x mb-3 d-block" style="opacity:.2;"></i>
        Belum ada berita.
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach($displayNews as $i => $n): ?>
        <div class="col-md-6 col-lg-4">
          <div class="news-card-full" style="animation-delay:<?= $i * .06 ?>s;">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="news-tag"><i class="fas fa-newspaper me-1"></i>Berita</span>
              <span style="color:var(--muted);font-size:.75rem;">
                <i class="fas fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($n['created_at'])) ?>
              </span>
            </div>
            <h5 style="font-family:'Clash Display',sans-serif;font-weight:700;color:#fff;font-size:1rem;margin-bottom:10px;line-height:1.4;">
              <?= htmlspecialchars($n['title']) ?>
            </h5>
            <p class="news-body-preview" style="color:var(--muted);font-size:.88rem;line-height:1.7;margin:0;">
              <?= htmlspecialchars($n['body']) ?>
            </p>
            <p class="news-body-full" style="color:var(--muted);font-size:.88rem;line-height:1.7;margin:0;">
              <?= htmlspecialchars($n['body']) ?>
            </p>
            <button class="read-more-btn d-inline-flex align-items-center gap-1 mt-3 read-more-btn"
                    onclick="toggleNews(this)"
                    style="color:var(--accent);font-size:.82rem;font-weight:700;text-decoration:none;">
              Baca selengkapnya <i class="fas fa-arrow-right" style="font-size:.7rem;" id="arrow"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
 

<!--footer-->
<footer>
  <div class="container">
    <div class="row g-4 pb-4" style="border-bottom:1px solid var(--border);">
 
     
      <div class="col-lg-4 col-md-6">
        <div class="navbar-brand mb-2">Lostn<span class="accent">Found</span></div>
        <div style="color:var(--muted);font-size:.85rem;line-height:1.7;max-width:280px;">
          Forum penemuan barang hilang di transportasi kereta commuterlink nusantara.
        </div>
        
        <div class="d-flex gap-3 mt-3">
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">
            <i class="fab fa-facebook"></i>
          </a>
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#5865f2'" onmouseout="this.style.color='#64748b'">
            <i class="fab fa-discord"></i>
          </a>
        </div>
      </div>
 
   
      <div class="col-lg-2 col-md-3 col-6">
        <div style="color:#e2e8f0;font-weight:700;font-size:.85rem;margin-bottom:12px;font-family:'Clash Display',sans-serif;">Quick Links</div>
        <div class="d-flex flex-column gap-2">
          <a href="items.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Browse Items</a>
          <a href="news.php"  style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">News</a>
          <a href="about.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">About</a>
        </div>
      </div>
 
   
      <div class="col-lg-2 col-md-3 col-6">
        <div style="color:#e2e8f0;font-weight:700;font-size:.85rem;margin-bottom:12px;font-family:'Clash Display',sans-serif;">Akun</div>
        <div class="d-flex flex-column gap-2">
          <?php if (isLoggedIn()): ?>
            <a href="dashboard/index.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Dashboard</a>
            <a href="./dashboard/buat-laporan.php"       style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Post Item</a>
            <a href="Auth/logout.php"     style="color:#f87171;font-size:.85rem;text-decoration:none;">Logout</a>
          <?php else: ?>
            <a href="./login.php"    style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Login</a>
            <a href="./register.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Register</a>
          <?php endif; ?>
        </div>
      </div>
 

      <div class="col-lg-4 col-md-6">
        <div style="color:#e2e8f0;font-weight:700;font-size:.85rem;margin-bottom:12px;font-family:'Clash Display',sans-serif;">Kontak</div>
        <div class="d-flex flex-column gap-2">
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-envelope me-2" style="color:var(--accent);"></i>lostnfound@krl.co.id</div>
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-phone me-2" style="color:var(--accent);"></i>021-1234-5678</div>
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-map-marker-alt me-2" style="color:var(--accent);"></i>Jakarta, Indonesia</div>
        </div>
      </div>
 
    </div>
 

    <div class="d-flex justify-content-between align-items-center pt-4 flex-wrap gap-2">
      <div style="color:var(--muted);font-size:.82rem;">© <?= date('Y') ?> LostnFound · Powered by Commuterlink Nusantara</div>
    </div>
 
  </div>
</footer>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleNews(btn) {
  const card  = btn.closest('.news-card-full');
  const arrow = btn.querySelector('#arrow');
  card.classList.toggle('expanded');
  if (card.classList.contains('expanded')) {
    btn.innerHTML = 'Tutup <i class="fas fa-arrow-up" style="font-size:.7rem;"></i>';
  } else {
    btn.innerHTML = 'Baca selengkapnya <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>';
  }
}
 
function toggleDropdown() {
  const dd = document.getElementById('avatarDropdown');
  dd.classList.toggle('hidden');
  if (!dd.classList.contains('hidden')) {
    dd.style.opacity='0'; dd.style.transform='translateY(-8px)';
    dd.style.transition='opacity .18s, transform .18s';
    requestAnimationFrame(() => { dd.style.opacity='1'; dd.style.transform='none'; });
  }
}
document.addEventListener('click', e => {
  const wrap = document.getElementById('avatarWrap');
  if (wrap && !wrap.contains(e.target)) document.getElementById('avatarDropdown')?.classList.add('hidden');
});
 

function toggleMobileMenu() {
  const menu = document.getElementById('mobileMenu');
  const icon = document.getElementById('hamburgerIcon');
  const isHidden = menu.classList.contains('nav-hidden');
  menu.classList.toggle('nav-hidden', !isHidden);
  menu.classList.toggle('nav-visible', isHidden);
  icon.className = isHidden ? 'fas fa-times text-lg' : 'fas fa-bars text-lg';
}
</script>
</body>
</html>