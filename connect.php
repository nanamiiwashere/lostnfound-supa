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
    $host = getenv('DB_HOST') ?: '';
    $port = getenv('DB_PORT') ?: '';
    $dbname = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') ?: '';
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
