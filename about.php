<?php
require_once './connect.php';
require_once './Auth/auth3thparty.php';

// Stats publik dari
$totalBarang   = (int)$pdo->query("SELECT COUNT(*) FROM barang_temuan")->fetchColumn();
$totalResolved = (int)$pdo->query("SELECT COUNT(*) FROM barang_temuan WHERE status='resolved'")->fetchColumn();
$totalStasiun  = (int)$pdo->query("SELECT COUNT(DISTINCT lokasi_ditemukan) FROM barang_temuan")->fetchColumn();
$totalUser     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tentang — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={corePlugins:{preflight:false}}</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="./style.css">
</head>
<body>

<div class="custom-cursor" id="cursor"></div>
<div class="cursor-trail" id="cursorTrail"></div>


<nav class="sticky top-0 z-50 border-b" style="background:rgba(8,12,20,.85);backdrop-filter:blur(16px);border-color:rgba(255,255,255,.07);" id="mainNav">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
    <a href="index.php" class="font-bold text-2xl text-white no-underline tracking-tight" style="font-family:'Clash Display',sans-serif;">
      Lostn<span style="color:#f97316;">Found</span>
    </a>
    <ul class="hidden lg:flex items-center gap-1 list-none m-0 p-0">
      <li><a href="index.php"  class="nav-link">Beranda</a></li>
      <li><a href="items.php"  class="nav-link">Cari Barang</a></li>
      <li><a href="news.php"   class="nav-link">Berita</a></li>
      <li><a href="about.php"  class="nav-link active">Tentang</a></li>
    </ul>
    <div class="hidden lg:flex items-center gap-2">
      <?php if (isLoggedIn()): $u = currentUser(); ?>
        <a href="./dashboard/buat-laporan.php" class="btn-nav-accent"><i class="fas fa-plus me-1"></i>Post Item</a>
        <div class="relative" id="avatarWrap">
          <button onclick="toggleDropdown()" class="btn-avatar">
            <?php if (!empty($u['avatar'])): ?>
              <?php $av=$u['avatar']; $avSrc=str_starts_with($av,'http')?$av:'uploads/'.$av; ?>
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
      <li><a href="index.php"  class="nav-link block">Beranda</a></li>
      <li><a href="items.php"  class="nav-link block">Cari Barang</a></li>
      <li><a href="news.php"   class="nav-link block">Berita</a></li>
      <li><a href="about.php"  class="nav-link active block">Tentang</a></li>
    </ul>
    <div class="flex flex-col gap-2 pt-2" style="border-top:1px solid rgba(255,255,255,.07);">
      <?php if (isLoggedIn()): ?>
        <a href="./dashboard/index.php" class="btn-nav-ghost text-center">Dashboard</a>
        <a href="./Auth/logout.php"       class="btn-nav-ghost text-center" style="color:#f87171;">Logout</a>
      <?php else: ?>
        <a href="./login.php"    class="btn-nav-ghost text-center">Login</a>
        <a href="./register.php" class="btn-nav-accent text-center">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>


<section class="about-hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">

  
      <div class="col-lg-6">
        <div class="hero-tag mb-4 fade-in-up">
          <span style="width:6px;height:6px;background:var(--accent);border-radius:50%;display:inline-block;"></span>
          Platform Barang Hilang KRL
        </div>
        <h1 style="font-size:clamp(2.5rem,6vw,4.5rem);font-weight:700;line-height:1.05;letter-spacing:-.03em;margin-bottom:1.2rem;" class="fade-in-up delay-1">
          Menghubungkan<br/><span style="color:var(--accent);">Barang & Pemiliknya</span>
        </h1>
        <p style="color:var(--muted);font-size:1rem;line-height:1.8;max-width:480px;margin-bottom:2rem;" class="fade-in-up delay-2">
          LostnFound adalah platform digital resmi KRL Commuterline Nusantara untuk memudahkan pelaporan, pencarian, dan pengembalian barang hilang di seluruh jaringan kereta commuterline.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-4 fade-in-up delay-3">
          <div class="stat-pill">
            <div>
              <div class="stat-pill-num"><?= $totalBarang ?>+</div>
              <div class="stat-pill-lbl">Barang Tercatat</div>
            </div>
          </div>
          <div class="stat-pill">
            <div>
              <div class="stat-pill-num" style="color:#22c55e;"><?= $totalResolved ?>+</div>
              <div class="stat-pill-lbl">Berhasil Kembali</div>
            </div>
          </div>
          <div class="stat-pill">
            <div>
              <div class="stat-pill-num" style="color:#818cf8;"><?= $totalStasiun ?>+</div>
              <div class="stat-pill-lbl">Stasiun</div>
            </div>
          </div>
        </div>

        <div class="d-flex gap-3 flex-wrap fade-in-up delay-4">
          <a href="items.php"           class="btn-primary-custom"><i class="fas fa-search"></i>Cari Barang</a>
          <a href="./register.php"   class="btn-ghost-custom"><i class="fas fa-user-plus"></i>Daftar Gratis</a>
        </div>
      </div>


      <div class="col-lg-6 d-none d-lg-block">
        <div class="hero-scene">
          <div class="hero-object" id="heroObject">

            <div class="card-3d card-main">
              <i class="fas fa-search"></i>
              <span>LostnFound</span>
              <div style="font-size:.65rem;color:#64748b;font-family:'Cabinet Grotesk',sans-serif;font-weight:400;">KRL Commuterline</div>
            </div>

            <div class="card-3d card-badge-1">
              <i class="fas fa-wallet" style="font-size:1.5rem;"></i>
              <div style="font-size:.65rem;margin-top:5px;opacity:.9;">Dompet</div>
            </div>
            <div class="card-3d card-badge-2">
              <i class="fas fa-mobile-alt" style="font-size:1.5rem;"></i>
              <div style="font-size:.65rem;margin-top:5px;opacity:.9;">Handphone</div>
            </div>
            <div class="card-3d card-badge-3">
              <i class="fas fa-key" style="font-size:1.3rem;"></i>
              <div style="font-size:.65rem;margin-top:5px;opacity:.9;">Kunci</div>
            </div>
            <div class="card-line"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


<section class="py-5" style="background:var(--surface);border-top:1px solid var(--border);">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Cara Kerja</p>
      <h2 class="section-title">Mudah, Cepat, Transparan</h2>
      <p style="color:var(--muted);max-width:480px;margin:10px auto 0;font-size:.92rem;">Proses dari laporan hingga barang kembali ke tangan pemiliknya.</p>
    </div>
    <div class="row g-4">
      <?php foreach([
        ['step'=>'01','icon'=>'fa-file-alt','color'=>'#f97316','title'=>'Buat Laporan','desc'=>'User mendaftarkan laporan kehilangan dengan deskripsi lengkap — nama barang, lokasi, tanggal, dan foto jika ada.'],
        ['step'=>'02','icon'=>'fa-search','color'=>'#818cf8','title'=>'Petugas Mencari','desc'=>'Tim petugas KRL menginput barang temuan ke sistem. Algoritma mendeteksi potensi kecocokan secara otomatis.'],
        ['step'=>'03','icon'=>'fa-link','color'=>'#22c55e','title'=>'Pencocokan','desc'=>'User mengajukan klaim. Staff memverifikasi kecocokan antara laporan kehilangan dan barang temuan.'],
        ['step'=>'04','icon'=>'fa-handshake','color'=>'#38bdf8','title'=>'Serah Terima','desc'=>'Setelah terverifikasi, serah terima dicatat resmi. Barang dikembalikan langsung ke pemiliknya di stasiun.'],
      ] as $i => $s): ?>
      <div class="col-sm-6 col-lg-3 fade-in-up" style="transition-delay:<?= $i*.1 ?>s;">
        <div class="step-card" data-step="<?= $s['step'] ?>">
          <div class="step-icon" style="background:<?= $s['color'] ?>1a;">
            <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;"></i>
          </div>
          <div class="step-title"><?= $s['title'] ?></div>
          <div class="step-desc"><?= $s['desc'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 fade-in-up">
        <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Fitur Unggulan</p>
        <h2 class="section-title mb-3">Dirancang untuk<br/>Kemudahan Kamu</h2>
        <p style="color:var(--muted);line-height:1.8;font-size:.92rem;">Platform yang dibangun dari perspektif pengguna — sederhana di permukaan, powerful di balik layar.</p>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach([
            ['icon'=>'fa-bolt','color'=>'#f97316','title'=>'Real-time Notifikasi','desc'=>'Dapat pemberitahuan langsung saat barangmu dicocokkan oleh staff.'],
            ['icon'=>'fa-shield-alt','color'=>'#22c55e','title'=>'Verifikasi 2 Lapis','desc'=>'Staff memverifikasi setiap klaim sebelum barang diserahterimakan.'],
            ['icon'=>'fa-map-marker-alt','color'=>'#818cf8','title'=>'Multi-Stasiun','desc'=>'Mencakup seluruh jaringan KRL Commuterline Nusantara.'],
            ['icon'=>'fa-history','color'=>'#38bdf8','title'=>'Riwayat Lengkap','desc'=>'Semua aktivitas tercatat — dari laporan hingga serah terima.'],
            ['icon'=>'fa-image','color'=>'#f472b6','title'=>'Upload Foto','desc'=>'Sertakan foto barang untuk mempercepat proses pencocokan.'],
            ['icon'=>'fa-users','color'=>'#fde047','title'=>'Komunitas KRL','desc'=>'Digunakan oleh ribuan pengguna KRL setiap harinya.'],
          ] as $f): ?>
          <div class="col-sm-6 fade-in-up">
            <div class="feat-card">
              <div style="width:40px;height:40px;border-radius:10px;background:<?= $f['color'] ?>1a;display:flex;align-items:center;justify-content:center;margin-bottom:.8rem;">
                <i class="fas <?= $f['icon'] ?>" style="color:<?= $f['color'] ?>;"></i>
              </div>
              <div style="font-weight:700;color:var(--text);margin-bottom:4px;font-size:.9rem;"><?= $f['title'] ?></div>
              <div style="color:var(--muted);font-size:.82rem;line-height:1.6;"><?= $f['desc'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>


<section class="py-5" style="background:var(--surface);border-top:1px solid var(--border);">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-4 fade-in-up">
        <p style="color:var(--accent);font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Perjalanan Kami</p>
        <h2 class="section-title mb-3">Dari Ide ke Realita</h2>
        <p style="color:var(--muted);font-size:.92rem;line-height:1.8;">LostnFound lahir dari keresahan nyata pengguna KRL yang kehilangan barang tanpa tahu harus lapor ke mana.</p>
      </div>
      <div class="col-lg-8 fade-in-up delay-2">
        <div class="timeline">
          <?php foreach([
            ['year'=>'2024','icon'=>'fa-lightbulb','title'=>'Ide Awal','desc'=>'Konsep platform digital untuk barang hilang KRL mulai dirancang setelah survei kepada pengguna commuterline.'],
            ['year'=>'2025 Q1','icon'=>'fa-code','title'=>'Pengembangan','desc'=>'Tim developer mulai membangun sistem manajemen laporan, pencocokan, dan serah terima barang.'],
            ['year'=>'2025 Q3','icon'=>'fa-rocket','title'=>'Beta Launch','desc'=>'Platform diluncurkan perdana di 5 stasiun percontohan dengan respons yang sangat positif dari pengguna.'],
            ['year'=>'2026','icon'=>'fa-train','title'=>'Ekspansi Penuh','desc'=>'LostnFound resmi beroperasi di seluruh jaringan KRL Commuterline Nusantara dengan fitur notifikasi real-time.'],
          ] as $tl): ?>
          <div class="tl-item">
            <div class="tl-dot"><i class="fas <?= $tl['icon'] ?>"></i></div>
            <div class="tl-content">
              <div class="tl-year"><?= $tl['year'] ?></div>
              <div class="tl-title"><?= $tl['title'] ?></div>
              <div class="tl-desc"><?= $tl['desc'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>


<section class="py-5">
  <div class="container">
    <div class="cta-banner fade-in-up">
      <div class="mb-3" style="font-size:2.5rem;"><i class="fas fa-search"></i></div>
      <h2 class="section-title mb-3">Kehilangan Barang di KRL?</h2>
      <p style="color:var(--muted);max-width:480px;margin:0 auto 1.5rem;font-size:.95rem;">
        Buat laporan sekarang dan biarkan sistem kami bekerja. Ribuan barang sudah berhasil dikembalikan.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="./register.php" class="btn-primary-custom"><i class="fas fa-user-plus"></i>Buat Akun Gratis</a>
        <a href="items.php"           class="btn-ghost-custom"><i class="fas fa-search"></i>Lihat Barang Temuan</a>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="row g-4 pb-4" style="border-bottom:1px solid var(--border);">
      <div class="col-lg-4 col-md-6">
        <div class="navbar-brand mb-2">Lostn<span class="accent">Found</span></div>
        <div style="color:var(--muted);font-size:.85rem;line-height:1.7;max-width:280px;">Forum penemuan barang hilang di transportasi kereta commuterlink nusantara.</div>
        <div class="d-flex gap-3 mt-3">
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'"><i class="fab fa-instagram"></i></a>
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'"><i class="fab fa-twitter"></i></a>
          <a href="#" style="color:var(--muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='#5865f2'" onmouseout="this.style.color='#64748b'"><i class="fab fa-discord"></i></a>
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

const cursor      = document.getElementById('cursor');
const cursorTrail = document.getElementById('cursorTrail');
let mx = 0, my = 0, tx = 0, ty = 0;

document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.left = mx + 'px';
  cursor.style.top  = my + 'px';
});

function animTrail() {
  tx += (mx - tx) * 0.12;
  ty += (my - ty) * 0.12;
  cursorTrail.style.left = tx + 'px';
  cursorTrail.style.top  = ty + 'px';
  requestAnimationFrame(animTrail);
}
animTrail();


const heroObj = document.getElementById('heroObject');
if (heroObj) {
  document.addEventListener('mousemove', e => {
    const rect = heroObj.closest('.hero-scene').getBoundingClientRect();
    const cx = rect.left + rect.width  / 2;
    const cy = rect.top  + rect.height / 2;
    const dx = (e.clientX - cx) / (rect.width  / 2);
    const dy = (e.clientY - cy) / (rect.height / 2);
    const rotX = -dy * 18;
    const rotY =  dx * 18;
    heroObj.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg)`;
  });

  document.addEventListener('mouseleave', () => {
    heroObj.style.transition = 'transform 0.6s ease';
    heroObj.style.transform = 'rotateX(0deg) rotateY(0deg)';
    setTimeout(() => heroObj.style.transition = 'transform 0.08s ease-out', 600);
  });
}

const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.15 });
document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

function toggleDropdown() {
  document.getElementById('avatarDropdown').classList.toggle('hidden');
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