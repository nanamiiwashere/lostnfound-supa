<?php
require_once './connect.php';
require_once './Auth/auth3thparty.php';
require_once './Auth/auth-handler.php';

if (isLoggedIn()){
    header('Location: ' . APP_URL . '/dashboard/index.php');
    exit();
}

$googleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' .http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
]);

$discordUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URL,
    'response_type' => 'code',
    'scope' => 'openid email profile',
]);

$urlError = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — LostnFound</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Cabinet+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="./style.css"/>
</head>
<body class="auth-body">
 
<div id="page-loader"><div class="loader-ring"></div></div>
 
<div id="nprogress-bar" id="nbar"></div>
 
<div class="auth-card">
 
  <div class="text-center mb-4">
    <a href="../index.php" class="text-decoration-none">
      <div style="font-family:'Clash Display',sans-serif;font-weight:700;font-size:2rem;color:#fff;">
        Lostn<span class="accent">Found</span>
      </div>
    </a>
    <p style="color:var(--muted);font-size:.88rem;margin-top:6px;">Welcome back! Sign in to continue.</p>
  </div>
 
  <?php if (!empty($error)): ?>
    <div class="auth-alert-err mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($urlError)): ?>
    <div class="auth-alert-err mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars(urldecode($urlError)) ?></div>
  <?php endif; ?>
 
  <!-- OAuth -->
  <a href="<?= $googleUrl ?>" class="oauth-btn mb-3" onclick="startProgress()">
    <i class="fa-brands fa-google" style="color:#4285F4;font-size:1.1rem;"></i>
    Continue with Google
  </a>
  <a href="<?= $discordUrl ?>" class="oauth-btn discord mb-4" onclick="startProgress()">
    <i class="fab fa-discord" style="color:#5865f2;font-size:1.1rem;"></i>
    Continue with Discord
  </a>
 
  <div class="auth-divider mb-4">or sign in with email</div>
 
  <form method="POST" action="" onsubmit="submitLoading(this)">
    <div class="mb-3">
      <label class="auth-label">Email</label>
      <input type="email" name="email" class="auth-input"
             placeholder="you@example.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
    </div>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="auth-label mb-0">Password</label>
        <a href="#" style="color:var(--accent);font-size:.78rem;text-decoration:none;">Forgot?</a>
      </div>
      <div class="position-relative">
        <input type="password" name="password" id="pwField" class="auth-input" placeholder="••••••••" required/>
        <button type="button" class="pw-eye" onclick="togglePw()">
          <i class="fas fa-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="auth-btn mb-3" id="submitBtn">
      <i class="fas fa-sign-in-alt me-2"></i>Sign In
    </button>
  </form>
 
  <p class="text-center mb-0" style="color:var(--muted);font-size:.85rem;">
    No account?
    <a href="register.php" style="color:var(--accent);font-weight:700;text-decoration:none;" onclick="startProgress()">Register free</a>
  </p>
 
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Hide loader when page loaded
window.addEventListener('load', () => {
  const loader = document.getElementById('page-loader');
  loader.classList.add('hide');
  setTimeout(() => loader.remove(), 400);
});
 
// NProgress bar for link navigation
function startProgress() {
  const bar = document.getElementById('nprogress-bar');
  bar.style.width = '0';
  bar.style.opacity = '1';
  let w = 0;
  const iv = setInterval(() => {
    w = Math.min(w + Math.random() * 15, 85);
    bar.style.width = w + '%';
  }, 150);
  window.addEventListener('beforeunload', () => {
    clearInterval(iv);
    bar.style.width = '100%';
    setTimeout(() => bar.style.opacity = '0', 200);
  });
}
 
function submitLoading(form) {
  const btn = form.querySelector('#submitBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Signing in...';
}
 
// Show/hide password
function togglePw() {
  const f = document.getElementById('pwField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type = 'text';     i.className = 'fas fa-eye-slash'; }
  else                       { f.type = 'password'; i.className = 'fas fa-eye'; }
}
</script>
</body>
</html>