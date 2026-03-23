<?php 
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="<?= APP_URL ?>index.php">Lostn<span class="accent">Found</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <i class="fas fa-bars" style="color:#e2e8f0;"></i>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'items.php' ? 'active' : '' ?>" href="<?= APP_URL ?>items.php">Browse Items</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'news.php' ? 'active' : '' ?>" href="<?= APP_URL ?>news.php">News</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= APP_URL ?>about.php">About</a>
        </li>
      </ul>
 
      <div class="d-flex align-items-center gap-2">
        <?php if (isLoggedIn()):
              $u = currentUser(); ?>
 
          <a href="<?= APP_URL ?>post-item.php" class="btn-nav-accent">
            <i class="fas fa-plus me-1"></i>Post Item
          </a>
          <div class="dropdown">
            <button class="btn-avatar dropdown-toggle" data-bs-toggle="dropdown">
              <?php if (!empty($u['avatar'])): ?>
                <img src="<?= htmlspecialchars($u['avatar']) ?>" class="avatar-img" alt=""/>
              <?php else: ?>
                <div class="avatar-initial"><?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?></div>
              <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-dark-custom">
              <li class="dropdown-header">
                <div class="fw-600 text-white small"><?= htmlspecialchars($u['name'] ?? '') ?></div>
                <div style="font-size:.7rem;color:var(--muted);">
                  <?php $icons = ['google'=>'fab fa-google','discord'=>'fab fa-discord','email'=>'fas fa-envelope']; ?>
                  <i class="<?= $icons[$u['provider']] ?? 'fas fa-user' ?> me-1"></i>
                  <?= ucfirst($u['provider'] ?? 'email') ?>
                </div>
              </li>
              <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.08);"/></li>
              <li><a class="dropdown-item" href="<?= APP_URL ?>dashboard/index.php"><i class="fas fa-th-large me-2"></i>Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= APP_URL ?>dashboard/my-items.php"><i class="fas fa-list me-2"></i>My Items</a></li>
              <li><a class="dropdown-item" href="<?= APP_URL ?>dashboard/messages.php"><i class="fas fa-envelope me-2"></i>Messages</a></li>
              <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.08);"/></li>
              <li><a class="dropdown-item text-danger-soft" href="<?= APP_URL ?>Auth/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
              </a></li>
            </ul>
          </div>
 
        <?php else: ?>
          <!-- Guest -->
          <a href="<?= APP_URL ?>Auth/login.php"    class="btn-nav-ghost">Login</a>
          <a href="<?= APP_URL ?>Auth/register.php" class="btn-nav-accent">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>