<?php
require_once './connect.php';
require_once './Auth/auth-handler.php';
require_once './core/envPrivilege.php';

$stmt = $pdo->query("SELECT * FROM barang_temuan WHERE status='open' ORDER BY created_at DESC LIMIT 8");
$barang_temuan = $stmt->fetchAll();


// Fetch News 
$news = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Stats
$totalLost  = $pdo->query("SELECT COUNT(*) FROM barang_temuan WHERE type='lost'")->fetchColumn();
$totalFound = $pdo->query("SELECT COUNT(*) FROM barang_temuan WHERE type='found'")->fetchColumn();
$resolved   = $pdo->query("SELECT COUNT(*) FROM barang_temuan WHERE status='resolved'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>LostnFound — Commuterlink Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="./style.css">
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="sticky top-0 z-50 border-b" style="background:rgba(8,12,20,.85);backdrop-filter:blur(16px);border-color:rgba(255,255,255,.07);" id="mainNav">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
 
    <!-- Logo -->
    <a href="<?= APP_URL ?>/index.php" class="font-bold text-2xl text-white no-underline tracking-tight" style="font-family:'Clash Display',sans-serif;">
      Lostn<span style="color:#f97316;">Found</span>
    </a>
 
    <!-- Desktop nav links -->
    <ul class="hidden lg:flex items-center gap-1 list-none m-0 p-0">
      <li><a href="index.php"  class="nav-link active">Beranda</a></li>
      <li><a href="items.php"  class="nav-link">Cari Barang</a></li>
      <li><a href="news.php"   class="nav-link">Berita</a></li>
      <li><a href="about.php"  class="nav-link">Tentang</a></li>
    </ul>
 
    <!-- Desktop right side -->
    <div class="hidden lg:flex items-center gap-2">
      <?php if (isLoggedIn()):
            $u = currentUser(); ?>
 
        <a href="./dashboard/buat-laporan.php" class="btn-nav-accent"><i class="fas fa-plus me-1"></i>Post Item</a>
 
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
          <!-- Dropdown -->
          <div id="avatarDropdown"
               class="hidden absolute right-0 mt-2 w-52 rounded-2xl py-2 z-50"
               style="background:#1a2332;border:1px solid rgba(255,255,255,.07);box-shadow:0 20px 40px rgba(0,0,0,.5);">
            <!-- User info -->
            <div class="px-4 py-2">
              <div class="text-white text-sm font-semibold"><?= htmlspecialchars($u['name']) ?></div>
              <div class="text-xs mt-0.5" style="color:#64748b;">
                <?php $icons=['google'=>'fab fa-google','discord'=>'fab fa-discord','email'=>'fas fa-envelope']; ?>
                <i class="<?= $icons[$u['provider']]??'fas fa-user' ?> mr-1"></i><?= ucfirst($u['provider']) ?>
              </div>
            </div>
            <hr style="border-color:rgba(255,255,255,.08);margin:4px 0;"/>
            <a href="./dashboard/index.php"   class="dropdown-dark-item"><i class="fas fa-th-large mr-2"></i>Dashboard</a>
            <a href="./dashboard/profil.php"class="dropdown-dark-item"><i class="fas fa-user-circle mr-2"></i>Profile</a>
            <a href="./dashboard/messages.php"class="dropdown-dark-item"><i class="fas fa-envelope mr-2"></i>Messages</a>
            <hr style="border-color:rgba(255,255,255,.08);margin:4px 0;"/>
            <a href="Auth/logout.php" class="dropdown-dark-item" style="color:#f87171;"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
          </div>
        </div>
 
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost">Login</a>
        <a href="./register.php" class="btn-nav-accent">Register</a>
      <?php endif; ?>
    </div>

   
    <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-300 p-2" style="background:none;border:none;">
      <i class="fas fa-bars text-lg" id="hamburgerIcon"></i>
    </button>
  </div>
 

  <div id="mobileMenu" class="hidden lg:hidden border-t px-4 py-3" style="border-color:rgba(255,255,255,.07);background:rgba(8,12,20,.97);">
    <ul class="list-none m-0 p-0 flex flex-col gap-1 mb-3">
      <li><a href="index.php"  class="nav-link block">Beranda</a></li>
      <li><a href="items.php"  class="nav-link block">Cari Barang</a></li>
      <li><a href="news.php"   class="nav-link block">Berita</a></li>
      <li><a href="about.php"  class="nav-link block">Tentang</a></li>
    </ul>
    <div class="flex flex-col gap-2 pt-2" style="border-top:1px solid rgba(255,255,255,.07);">
      <?php if (isLoggedIn()): ?>
        <a href="./dashboard/buat-laporan.php"         class="btn-nav-accent text-center">Post Item</a>
        <a href="./dashboard/index.php"   class="btn-nav-ghost text-center">Dashboard</a>
        <a href="Auth/logout.php"       class="btn-nav-ghost text-center" style="color:#f87171;border-color:rgba(239,68,68,.3);">Logout</a>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost text-center">Login</a>
        <a href="./register.php" class="btn-nav-accent text-center">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>


<section class="hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-tag mb-4">
          <span style="width:6px;height:6px;background:var(--accent);border-radius:50%;display:inline-block;"></span>
          Pengaduan barang hilang krl commuterlink nusantara
        </div>
        <h1 class="hero-title mb-4">
          Barang hilang?<br/><span class="accent">Kita bantu cari.</span>
        </h1>
        <p class="hero-sub mb-5">
          Cari barang hilang & temuan dari stasiun KRL.
          <?php if (!isLoggedIn()): ?>
            Daftar gratis untuk lapor atau hubungi petugas.
          <?php else: ?>
            Post an item or contact staff from your dashboard.
          <?php endif; ?>
        </p>


        <div class="search-bar mb-4">
          <input type="text" id="heroSearch" placeholder="Cari kehilangan kunci, dompet, kartu ..."/>
          <button onclick="location.href='items.php?q='+document.getElementById('heroSearch').value">
            <i class="fas fa-search"></i>
          </button>
        </div>

  
        <div class="d-flex flex-wrap gap-3">
          <?php if (isLoggedIn()): ?>
            
            <a href="./dashboard/buat-laporan.php"         class="btn-primary-custom"><i class="fas fa-plus-circle"></i> Report Item</a>
            <a href="items.php?type=found"  class="btn-ghost-custom"><i class="fas fa-hand-holding"></i> Found Items</a>
          <?php else: ?>
   
            <a href="items.php"             class="btn-primary-custom"><i class="fas fa-search"></i> Explore Items</a>
            <a href="/register.php"     class="btn-ghost-custom"><i class="fas fa-user-plus"></i> Register to Post</a>
          <?php endif; ?>
        </div>
      </div>


      <div class="col-lg-6">
        <div class="row g-3">
          <?php foreach([
            ['num'=>$totalLost,  'lbl'=>'Barang hilang',    'icon'=>'fa-exclamation-circle','color'=>'#ef4444'],
            ['num'=>$totalFound, 'lbl'=>'Barang ditemukan',   'icon'=>'fa-hand-holding',      'color'=>'#22c55e'],
            ['num'=>$resolved,   'lbl'=>'Berhasil dikembalikan',      'icon'=>'fa-handshake',         'color'=>'#f97316'],
            ['num'=>'24/7',      'lbl'=>'Dukungan Staff', 'icon'=>'fa-headset',           'color'=>'#818cf8'],
          ] as $s): ?>
          <div class="col-6">
            <div class="p-4 rounded-3" style="background:var(--card);border:1px solid var(--border);">
              <div class="d-flex align-items-center gap-3 mb-2">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:40px;height:40px;background:<?= $s['color'] ?>18;flex-shrink:0;">
                  <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;"></i>
                </div>
                <div style="font-family:'Clash Display',sans-serif;font-size:1.8rem;font-weight:700;color:<?= $s['color'] ?>;">
                  <?= $s['num'] ?>
                </div>
              </div>
              <div style="font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-weight:600;">
                <?= $s['lbl'] ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>


<section class="py-5 mt-2">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Latest Activity</p>
        <h2 class="section-title mb-0">Recent Items</h2>
      </div>
      <a href="items.php" class="btn-ghost-custom py-2 px-4" style="font-size:.85rem;">View All <i class="fas fa-arrow-right ms-1"></i></a>
    </div>


    <div class="d-flex gap-2 mb-4 flex-wrap">
      <button class="filter-tab active" onclick="filterItems('all',this)">All Items</button>
      <button class="filter-tab" onclick="filterItems('lost',this)">
        <span style="background:#ef4444;width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px;"></span>Lost
      </button>
      <button class="filter-tab" onclick="filterItems('found',this)">
        <span style="background:#22c55e;width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px;"></span>Found
      </button>
    </div>

    <div class="row g-4" id="itemsGrid">
      <?php
      $demos = [
        ['nama_barang'=>'Dompet Kulit Hitam',  'type'=>'lost',  'lokasi_ditemukan'=>'Taman Kota Blok M',       'category'=>'Accessories', 'image'=>null,'id_barang'=>1],
        ['nama_barang'=>'iPhone 14 Pro',       'type'=>'found', 'lokasi_ditemukan'=>'Halte Busway Sudirman',   'category'=>'Electronics', 'image'=>null,'id_barang'=>2],
        ['nama_barang'=>'Kucing Oranye Mochi', 'type'=>'lost',  'lokasi_ditemukan'=>'Bintaro Sektor 7',        'category'=>'Pets',        'image'=>null,'id_barang'=>3],
        ['nama_barang'=>'Tas Ransel Eiger',    'type'=>'found', 'lokasi_ditemukan'=>'Perpustakaan Nasional',   'category'=>'Bags',        'image'=>null,'id_barang'=>4],
        ['nama_barang'=>'Kunci Motor Yamaha',  'type'=>'lost',  'lokasi_ditemukan'=>'Parkiran Mall Senayan',   'category'=>'Keys',        'image'=>null,'id_barang'=>5],
        ['nama_barang'=>'Gelang Perak',        'type'=>'found', 'lokasi_ditemukan'=>'Pantai Ancol',            'category'=>'Jewelry',     'image'=>null,'id_barang'=>6],
        ['nama_barang'=>'Kartu Pelajar SMA',   'type'=>'found', 'lokasi_ditemukan'=>'Kantin Sekolah',          'category'=>'Documents',   'image'=>null,'id_barang'=>7],
        ['nama_barang'=>'AirPods Pro Gen 2',   'type'=>'lost',  'lokasi_ditemukan'=>'Kopi Kenangan Sudirman',  'category'=>'Electronics', 'image'=>null,'id_barang'=>8],
      ];
      $displayItems = empty($barang_temuan) ? $demos : $barang_temuan;
      $catIcons = ['Electronics'=>'fa-mobile-alt','Accessories'=>'fa-wallet','Pets'=>'fa-paw','Bags'=>'fa-shopping-bag','Keys'=>'fa-key','Jewelry'=>'fa-gem','Documents'=>'fa-id-card','Other'=>'fa-box'];

      foreach($displayItems as $item):
        $icon = $catIcons[$item['category']] ?? 'fa-box';
      ?>
      <div class="col-sm-6 col-lg-3 item-col" data-type="<?= $item['type'] ?>">
        <div class="item-card h-100" onclick="location.href='item-detail.php?id=<?= $item['id_barang'] ?>'">
          <div class="item-card-img-wrap">
            <?php if (!empty($item['image'])): ?>
              <img src="./uploads/<?= htmlspecialchars($item['image']) ?>" class="item-card-img" alt=""/>
            <?php else: ?>
              <div class="img-placeholder">
                <i class="fas <?= $icon ?> fa-2x" style="color:rgba(255,255,255,.15);"></i>
              </div>
            <?php endif; ?>
            <span class="type-badge <?= $item['type'] ?>"><?= strtoupper($item['type']) ?></span>
          </div>

          <div class="item-card-body">
            <div class="item-cat"><?= htmlspecialchars($item['category'] ?? 'Other') ?></div>
            <div class="item-title"><?= htmlspecialchars($item['nama_barang']) ?></div>
            <div class="item-loc mb-3">
              <i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($item['lokasi_ditemukan']) ?>
            </div>

            <?php if (isLoggedIn()): ?>
              <div class="d-flex gap-2">
                <a href="item-detail.php?id=<?= $item['id_barang'] ?>"
                   class="btn btn-sm flex-grow-1 fw-600"
                   style="background:rgba(249,115,22,.15);color:var(--accent);border-radius:8px;border:1px solid rgba(249,115,22,.2);"
                   onclick="event.stopPropagation()">
                  View
                </a>
                <?php if ($item['type'] === 'found'): ?>
                  <a href="claim-item.php?id=<?= $item['id_barang'] ?>"
                     class="btn btn-sm fw-600"
                     style="background:rgba(34,197,94,.12);color:#22c55e;border-radius:8px;border:1px solid rgba(34,197,94,.2);"
                     onclick="event.stopPropagation()">
                    Claim
                  </a>
                <?php else: ?>
                  <a href="contact-staff.php?ref=<?= $item['id_barang'] ?>"
                     class="btn btn-sm fw-600"
                     style="background:rgba(129,140,248,.12);color:#818cf8;border-radius:8px;border:1px solid rgba(129,140,248,.2);"
                     onclick="event.stopPropagation()">
                    Help
                  </a>
                <?php endif; ?>
              </div>

            <?php else: ?>
              <div class="guest-gate">
                <a href="./login.php"    onclick="event.stopPropagation()">Login</a>
                or
                <a href="./register.php" onclick="event.stopPropagation()">Register</a>
                to claim or contact staff
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- NEWS  -->
<section class="py-5" style="background:var(--surface);border-top:1px solid var(--border);">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Updates</p>
        <h2 class="section-title mb-0">Latest News</h2>
      </div>
      <a href="news.php" class="btn-ghost-custom py-2 px-4" style="font-size:.85rem;">All News <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4">
      <?php
      $demoNews = [
        ['title'=>'New Drop-off Point at City Hall',   'body'=>'We have set up a new drop-off point for found items at City Hall, open weekdays 9am–5pm.',          'created_at'=>'2025-01-10'],
        ['title'=>'January: 47 Items Reunited!',        'body'=>'Thanks to our community, 47 lost items were successfully returned to their owners this month.',      'created_at'=>'2025-01-08'],
        ['title'=>'How to Improve Your Listing',        'body'=>'Adding a clear photo and exact location increases your chance of recovery by 3x. Read our guide.',   'created_at'=>'2025-01-05'],
      ];
      $displayNews = empty($news) ? $demoNews : $news;
      foreach($displayNews as $n): ?>
      <div class="col-md-4">
        <div class="news-card h-100">
          <div class="news-date mb-2"><i class="fas fa-calendar-alt me-1"></i><?= date('M j, Y', strtotime($n['created_at'])) ?></div>
          <div class="news-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="news-body"><?= htmlspecialchars(substr($n['body'],0,110)) ?>...</div>
          <a href="news.php" class="d-inline-flex align-items-center gap-1 mt-3"
             style="color:var(--accent);font-size:.82rem;font-weight:700;text-decoration:none;">
            Read more <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CTA — only guests see this ══ -->
<?php if (!isLoggedIn()): ?>
<section class="py-5">
  <div class="container">
    <div class="cta-banner">
      <div class="mb-3" style="font-size:2.5rem;"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="section-title mb-3">Siap untuk lapor atau klaim barang?</h2>
      <p style="color:var(--muted);max-width:480px;margin:0 auto 1.5rem;">
        Buat akun gratis untuk lapor barang hilang, klaim barang temuan, dan langsung hubungi tim kami.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="./register.php" class="btn-primary-custom"><i class="fas fa-user-plus"></i> Buat akun</a>
        <a href="./login.php"    class="btn-ghost-custom"><i class="fas fa-sign-in-alt"></i> Sudah ada akun? Login</a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="container">
    <div class="row g-4 pb-4" style="border-bottom:1px solid var(--border);">
 
      <!-- Brand + desc -->
      <div class="col-lg-4 col-md-6">
        <div class="navbar-brand mb-2">Lostn<span class="accent">Found</span></div>
        <div style="color:var(--muted);font-size:.85rem;line-height:1.7;max-width:280px;">
          Forum penemuan barang hilang di transportasi kereta commuterlink nusantara.
        </div>
        <!-- Socmed -->
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
 
      <!-- Links -->
      <div class="col-lg-2 col-md-3 col-6">
        <div style="color:#e2e8f0;font-weight:700;font-size:.85rem;margin-bottom:12px;font-family:'Clash Display',sans-serif;">Quick Links</div>
        <div class="d-flex flex-column gap-2">
          <a href="items.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">Browse Items</a>
          <a href="news.php"  style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">News</a>
          <a href="about.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">About</a>
        </div>
      </div>
 
      <!-- Account -->
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
 
      <!-- Contact -->
      <div class="col-lg-4 col-md-6">
        <div style="color:#e2e8f0;font-weight:700;font-size:.85rem;margin-bottom:12px;font-family:'Clash Display',sans-serif;">Kontak</div>
        <div class="d-flex flex-column gap-2">
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-envelope me-2" style="color:var(--accent);"></i>lostnfound@krl.co.id</div>
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-phone me-2" style="color:var(--accent);"></i>021-1234-5678</div>
          <div style="color:var(--muted);font-size:.85rem;"><i class="fas fa-map-marker-alt me-2" style="color:var(--accent);"></i>Jakarta, Indonesia</div>
        </div>
      </div>
 
    </div>
 
    <!-- Copyright -->
    <div class="d-flex justify-content-between align-items-center pt-4 flex-wrap gap-2">
      <div style="color:var(--muted);font-size:.82rem;">© <?= date('Y') ?> LostnFound · Powered by Commuterlink Nusantara</div>
    </div>
 
  </div>
</footer>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterItems(type, btn) {
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.item-col').forEach(col => {
    col.style.display = (type === 'all' || col.dataset.type === type) ? '' : 'none';
  });
}  

// navbar section //
function toggleDropdown() {
  document.getElementById('avatarDropdown').classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
  const wrap = document.getElementById('avatarWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('avatarDropdown')?.classList.add('hidden');
  }
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