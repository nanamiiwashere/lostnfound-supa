<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../connect.php';
require_once '../Auth/auth3thparty.php';

if (!isLoggedIn()){
    http_response_code(401);
    exit();
}

$uid = $_SESSION['user_id'];
$notifKey = 'notif_read_' . $uid;

$q1 = $pdo->prepare("
    SELECT p.tanggal_pencocokan, l.id_laporan
    FROM pencocokan p
    JOIN laporan_kehilangan l ON p.id_laporan = l.id_laporan
    WHERE l.id_pelapor = ?
      AND p.status_verifikasi IN ('approved', 'rejected')
      AND l.status = 'open'
      ");

$q1->execute([$uid]);
$rows = $q1->fetchAll();

$keys = [];
foreach ($rows as $r){
    $keys[] = "detail-laporan.php?id={$r['id_laporan']}" . $r['tanggal_pencocokan'];
}

$existing = $_SESSION[$notifKey] ?? [];
$_SESSION[$notifKey] = array_unique(array_merge($existing, $keys));

http_response_code(200);