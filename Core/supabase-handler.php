<?php
require_once __DIR__ . '/../connect.php';

function uploadToSupabase(string $fileTmp, string $fileName, string $bucket): string|false {
    $url = SUPABASE_URL . '/storage/v1/object/' . $bucket . "/" . $fileName;

    $header = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/octet-stream",
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($fileTmp));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300){
        return SUPABASE_URL . '/storage/v1/object/public/' . $bucket . "/" . $fileName;
    }

    error_log("Upload failed: HTTP $httpCode - $response");
    return false;
}
?>