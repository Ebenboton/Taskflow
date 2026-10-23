<?php
// config/database.php

// 1. Détection éventuelle d'une URL complète (ex: DATABASE_URL ou MYSQL_URL)
$urlConfig = [];
$databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? null));
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
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? getenv('MYSQLHOST') ?: ($_ENV['MYSQLHOST'] ?? ($urlConfig['host'] ?? 'localhost')));
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? getenv('MYSQLPORT') ?: ($_ENV['MYSQLPORT'] ?? ($urlConfig['port'] ?? 3306)));
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? getenv('MYSQLDATABASE') ?: ($_ENV['MYSQLDATABASE'] ?? ($urlConfig['dbname'] ?? 'taskflow_db'))));
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? getenv('MYSQLUSER') ?: ($_ENV['MYSQLUSER'] ?? ($urlConfig['username'] ?? 'root'))));
$password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? getenv('MYSQLPASSWORD') ?: ($_ENV['MYSQLPASSWORD'] ?? ($urlConfig['password'] ?? ''))));

// 3. Détection et validation du SSL
$rawSsl = getenv('DB_SSL') ?: ($_ENV['DB_SSL'] ?? null);
$sslExplicit = false;

if ($rawSsl !== null) {
    $normalized = strtolower(trim((string)$rawSsl));
    $sslExplicit = in_array($normalized, ['oui', 'on'], true) || filter_var($rawSsl, FILTER_VALIDATE_BOOLEAN);
}

$hasCustomCa = !empty(getenv('DB_SSL_CA') ?: ($_ENV['DB_SSL_CA'] ?? null));
$isRemoteHost = !in_array($host, ['localhost', '127.0.0.1'], true);

$ssl = $sslExplicit || $hasCustomCa || $isRemoteHost;

return [
    'host'     => $host,
    'port'     => (int)$port,
    'dbname'   => $dbname,
    'username' => $username,
    'password' => $password,
    'charset'  => 'utf8mb4',
    'ssl'      => $ssl,
    'ssl_ca'   => getenv('DB_SSL_CA') ?: ($_ENV['DB_SSL_CA'] ?? null),
];