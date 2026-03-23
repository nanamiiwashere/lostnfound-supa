<?php
$activePage = $activePage ?? '';
$u = currentUser();
$isStaff = ($u['role'] ?? '') === 'staff';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">Lostn<span>Found</span></div>
  <nav style="flex:1;overflow-y:auto;">
    <nav style="flex:1;overflow-y:auto;">

    <div class="side-section-label">Menu</div>
    <a href="index.php"         class="side-link <?= $activePage==='home'?'active':'' ?>"><i class="fas fa-th-large"></i>Dashboard</a>
    <a href="laporan.php"       class="side-link <?= $activePage==='laporan'?'active':'' ?>"><i class="fas fa-file-alt"></i>Laporan Saya</a>
    <a href="buat-laporan.php"  class="side-link <?= $activePage==='buat'?'active':'' ?>"><i class="fas fa-plus-circle"></i>Buat Laporan</a>
    <a href="barang-temuan.php" class="side-link <?= $activePage==='temuan'?'active':'' ?>"><i class="fas fa-search"></i>Barang Temuan</a>
    <a href="klaim.php"         class="side-link <?= $activePage==='klaim'?'active':'' ?>"><i class="fas fa-hand-holding"></i>Riwayat Klaim</a>
    <?php if ($isStaff): ?>
    <div class="sidebar-divider"></div>
    <div class="side-section-label">Staff</div>
    <a href="staff-laporan.php"      class="side-link <?= $activePage==='s-laporan'?'active':'' ?>"><i class="fas fa-file-alt"></i>Kelola Laporan</a>
    <a href="staff-barang.php"       class="side-link <?= $activePage==='s-barang'?'active':'' ?>"><i class="fas fa-box-open"></i>Input Barang Temuan</a>
    <a href="staff-pencocokan.php"   class="side-link <?= $activePage==='s-pencocokan'?'active':'' ?>"><i class="fas fa-link"></i>Pencocokan</a>
    <a href="staff-serah.php"        class="side-link <?= $activePage==='s-serah'?'active':'' ?>"><i class="fas fa-handshake"></i>Serah Terima</a>
    <a href="staff-news.php"         class="side-link <?= $activePage==='s-news'?'active':'' ?>"><i class="fas fa-newspaper"></i>Berita</a>
    <?php endif; ?>
    <div class="sidebar-divider"></div>
    <div class="side-section-label">Preferences</div>
    <a href="profil.php"        class="side-link <?= $activePage==='profil'?'active':'' ?>"><i class="fas fa-user-circle"></i>Profil Saya</a>
    <a href="../index.php"      class="side-link"><i class="fas fa-home"></i>Kembali ke Beranda</a>
    <a href="../Auth/logout.php"class="side-link danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
  </nav>
  <div class="sidebar-user">
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if (!empty($u['avatar'])): ?>
        <img src="<?= htmlspecialchars($u['avatar']) ?>" class="user-avatar-sm" alt=""/>
      <?php else: ?>
        <div class="user-avatar-initial"><?= strtoupper(substr($u['name']??'U',0,1)) ?></div>
      <?php endif; ?>
      <div style="min-width:0;overflow:hidden;">
        <div style="color:#fff;font-weight:600;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($u['name']??'') ?></div>
        <?php $pIcons=['google'=>'fab fa-google','discord'=>'fab fa-discord','email'=>'fas fa-envelope']; ?>
        <div class="provider-pill mt-1"><i class="<?= $pIcons[$u['provider']]??'fas fa-user' ?>"></i> <?= ucfirst($u['provider']??'email') ?></div>
            <!-- if staff-->
      <?php if ($isStaff): ?>
        <span style="font-size:.55rem;background:rgba(249,115,22,.15);color:#f97316;border:1px solid rgba(249,115,22,.3);border-radius:6px;padding:2px 7px;vertical-align:middle;font-family:'Cabinet Grotesk',sans-serif;font-weight:700;margin-left:6px;">STAFF</span>
      <?php endif; ?>
      </div>
      </div>
    </div>
  </div>
</aside>