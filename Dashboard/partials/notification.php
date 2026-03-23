<?php
$uid = $u['id'] ?? $_SESSION['id_user'] ?? null;
$notifs = [];
$notifKey = 'notif_read_' . $uid;

if ($uid) {
    $q1 = $pdo->prepare("
        SELECT l.id_laporan, l.nama_barang, b.nama_barang AS barang_temuan,
               p.tanggal_pencocokan, p.id_pencocokan
        FROM pencocokan p
        JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
        JOIN barang_temuan b ON p.id_barang = b.id_barang
        WHERE l.id_pelapor = ?
          AND p.status_verifikasi = 'approved'
          AND l.status = 'open'
        ORDER BY p.tanggal_pencocokan DESC
    ");
    $q1->execute([$uid]);
    foreach ($q1->fetchAll() as $r) {
        $notifs[] = [
            'type'    => 'match',
            'icon'    => 'fa-link',
            'color'   => 'green',
            'title'   => 'Barang Kamu Dicocokkan!',
            'text'    => "Petugas mencocokkan \"{$r['nama_barang']}\" dengan \"{$r['barang_temuan']}\". Konfirmasi sekarang agar dapat diinfokan lebih lanjut.",
            'link'    => "detail-laporan.php?id={$r['id_laporan']}",
            'time'    => $r['tanggal_pencocokan'],
            'unread'  => true,
        ];
    }
 
    $q2 = $pdo->prepare("
        SELECT l.id_laporan, l.nama_barang, p.tanggal_pencocokan
        FROM pencocokan p
        JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
        WHERE l.id_pelapor = ?
          AND p.status_verifikasi = 'process'
        ORDER BY p.tanggal_pencocokan DESC
    ");
    $q2->execute([$uid]);
    foreach ($q2->fetchAll() as $r) {
        $notifs[] = [
            'type'    => 'process',
            'icon'    => 'fa-clock',
            'color'   => 'orange',
            'title'   => 'Klaim Sedang Ditinjau',
            'text'    => "Laporan \"{$r['nama_barang']}\" sedang diverifikasi oleh petugas. Silakan tunggu informasi lebih lanjut.",
            'link'    => "detail-laporan.php?id={$r['id_laporan']}",
            'time'    => $r['tanggal_pencocokan'],
            'unread'  => false,
        ];
    }
 
    $q3 = $pdo->prepare("
        SELECT l.id_laporan, l.nama_barang, st.tanggal_serah_terima, st.nama_penerima
        FROM serah_terima st
        JOIN pencocokan p ON st.id_pencocokan = p.id_pencocokan
        JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
        WHERE l.id_pelapor = ?
          AND l.status = 'closed'
        ORDER BY st.tanggal_serah_terima DESC
        LIMIT 3
    ");
    $q3->execute([$uid]);
    foreach ($q3->fetchAll() as $r) {
        $notifs[] = [
            'type'    => 'done',
            'icon'    => 'fa-check-circle',
            'color'   => 'blue',
            'title'   => 'Laporan Selesai',
            'text'    => "\"{$r['nama_barang']}\" telah diserahterimakan. Laporan ditutup.",
            'link'    => "detail-laporan.php?id={$r['id_laporan']}",
            'time'    => $r['tanggal_serah_terima'],
            'unread'  => false,
        ];
    }
 
    $q4 = $pdo->prepare("
        SELECT l.id_laporan, l.nama_barang, p.tanggal_pencocokan
        FROM pencocokan p
        JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
        WHERE l.id_pelapor = ?
          AND p.status_verifikasi = 'rejected'
          AND l.status = 'open'
        ORDER BY p.tanggal_pencocokan DESC
        LIMIT 3
    ");
    $q4->execute([$uid]);
    foreach ($q4->fetchAll() as $r) {
        $notifs[] = [
            'type'    => 'rejected',
            'icon'    => 'fa-times-circle',
            'color'   => 'red',
            'title'   => 'Pencocokan Ditolak',
            'text'    => "Pencocokan untuk \"{$r['nama_barang']}\" ditolak petugas. Coba cari barang temuan lain atau buat laporan baru.",
            'link'    => "detail-laporan.php?id={$r['id_laporan']}",
            'time'    => $r['tanggal_pencocokan'],
            'unread'  => true,
        ];
    }
}

$unreadCount = count(array_filter($notifs, fn($n) => $n['unread']));
$notifKey = 'notif_read_' . $uid;
$currentKeys = array_map(fn($n) => $n['link'] . $n['time'], array_filter($notifs, fn($n) => $n['unread']));
$readKeys = $_SESSION[$notifKey] ?? [];

$notifs = array_map(function($n) use ($readKeys) {
    $key = $n['link'] . $n['time'];
    if ($n['unread'] && in_array($key, $readKeys)) {
        $n['unread'] = false;
    }
    return $n;
}, $notifs);


$unreadCount = count(array_filter($notifs, fn($n) => $n['unread']));
function timeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff/60) . ' menit lalu';
    if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff/86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}
?>

<div class="notif-bell-wrap" id="notifWrap">
    <button class="notif-bell-btn" onclick="toggleNotif()" title="Notifikasi">
        <i class="fas fa-bell" style="font-size:.9rem;"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notif-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </button>
 
    <div class="notif-dropdown" id="notifDropdown" style="display:none;">
        <div class="notif-dropdown-header">
            <span class="notif-dropdown-title"><i class="fas fa-bell me-2" style="color:#f97316;"></i>Notifikasi</span>
            <?php if ($unreadCount > 0): ?>
                <span style="background:rgba(249,115,22,.15);color:#f97316;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:6px;"><?= $unreadCount ?> baru</span>
            <?php endif; ?>
        </div>
 
        <div class="notif-list">
            <?php if (empty($notifs)): ?>
                <div class="notif-empty">
                    <i class="fas fa-bell-slash"></i>
                    <div style="font-size:.85rem;">Belum ada notifikasi</div>
                </div>
            <?php else: ?>
                <?php foreach ($notifs as $n): ?>
                <a href="<?= $n['link'] ?>" class="notif-item <?= $n['unread'] ? 'unread' : '' ?>">
                    <div class="notif-icon <?= $n['color'] ?>">
                        <i class="fas <?= $n['icon'] ?>"></i>
                    </div>
                    <div class="notif-text">
                        <strong><?= $n['title'] ?></strong>
                        <?= htmlspecialchars($n['text']) ?>
                        <div class="notif-time"><i class="fas fa-clock me-1"></i><?= timeAgo($n['time']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
 
        <div style="padding:.7rem 1.1rem;border-top:1px solid rgba(255,255,255,.06);">
            <a href="laporan.php" style="color:#64748b;font-size:.78rem;text-decoration:none;display:flex;align-items:center;gap:5px;transition:color .2s;" onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#64748b'">
                <i class="fas fa-list-alt"></i>Lihat semua laporan saya
            </a>
        </div>
    </div>
</div>

<script>
function toggleNotif(){
    const dd = document.getElementById('notifDropdown');
    const isOpening = dd.style.display === 'none';
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
    if (isOpening){
        fetch('markasread.php', {
            method:'POST'
        }) .then (() => {
                const badge = document.querySelector('.notif-badge');
                if (badge) badge.remove();
            });
    }
}

document.addEventListener('click', function(e){
    const wrap = document.getElementById('notifWrap');
    if (wrap && !wrap.contains(e.target)){
        document.getElementById('notifDropdown').style.display = 'none';
    }
});

</script>