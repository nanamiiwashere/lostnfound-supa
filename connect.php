<?php

if (session_status() == PHP_SESSION_NONE){
        session_status();
}

$db_url = getenv('DATABASE_URL');
    
if ($db_url){
    #parse database url
    $parsed = parse_url($db_url);
    $host = $parsed['host'];
    $port = $parsed['port'] ?? 5432;
    $dbname = ltrim($parsed['path'], '/');
    $user = $parsed['user'];
    $pass = $parsed['pass'];
} else {
    #fallback local
    $host = getenv('DB_HOST') ?: 'db.sdtenasvwqdkunztwcyy.supabase.co';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'postgres';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: 'lMPlSFU4mfWBbKAG';
}

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}