<?php
// config/database.php

// 1. Détection éventuelle d'une URL complète (ex: DATABASE_URL ou MYSQL_URL)
$urlConfig = [];
$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($databaseUrl) {
    $parsed = parse_url($databaseUrl);
    if ($parsed) {
        $urlConfig = [
            'host'     => $parsed['host'] ?? null,
            'port'     => isset($parsed['port']) ? (int)$parsed['port'] : null,
            'dbname'   => isset($parsed['path']) ? ltrim($parsed['path'], '/') : null,
            'username' => $parsed['user'] ?? null,
            'password' => $parsed['pass'] ?? null,
        ];
    }
}

// 2. Variables d'environnement individuelles (avec fallbacks pour XAMPP local)
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: ($urlConfig['host'] ?? 'localhost');
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: ($urlConfig['port'] ?? 3306);
$dbname = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: ($urlConfig['dbname'] ?? 'taskflow_db');
$username = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: getenv('MYSQLUSER') ?: ($urlConfig['username'] ?? 'root');
$password = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: ($urlConfig['password'] ?? '');
$ssl = getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1' || !empty(getenv('DB_SSL_CA')) || ($host !== 'localhost' && $host !== '127.0.0.1');

return [
    'host'     => $host,
    'port'     => (int)$port,
    'dbname'   => $dbname,
    'username' => $username,
    'password' => $password,
    'charset'  => 'utf8mb4',
    'ssl'      => $ssl,
    'ssl_ca'   => getenv('DB_SSL_CA') ?: null,
];