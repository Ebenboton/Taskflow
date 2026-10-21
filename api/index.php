<?php
// api/index.php - Point d'entrée Serverless pour Vercel

$publicDir = realpath(__DIR__ . '/../public');

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = trim($path, '/');

// 1. Racine -> public/index.php
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    chdir($publicDir);
    require $publicDir . '/index.php';
    exit;
}

// 2. Vérifier si un fichier exact existe dans public/
$targetFile = realpath($publicDir . '/' . $path);

// Protection contre Directory Traversal
if ($targetFile && str_starts_with($targetFile, $publicDir)) {
    if (is_file($targetFile)) {
        // Si c'est un script PHP
        if (str_ends_with($targetFile, '.php')) {
            $_SERVER['SCRIPT_NAME'] = '/' . basename($targetFile);
            $_SERVER['PHP_SELF'] = '/' . basename($targetFile);
            chdir(dirname($targetFile));
            require $targetFile;
            exit;
        }

        // Si c'est un fichier statique (CSS, JS, images...)
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $mimes = [
            'css'  => 'text/css; charset=UTF-8',
            'js'   => 'application/javascript; charset=UTF-8',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];

        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        readfile($targetFile);
        exit;
    }
}

// 3. Vérifier avec extension .php (ex: /login -> /login.php)
if (!str_ends_with($path, '.php')) {
    $phpCandidate = realpath($publicDir . '/' . $path . '.php');
    if ($phpCandidate && str_starts_with($phpCandidate, $publicDir) && is_file($phpCandidate)) {
        $_SERVER['SCRIPT_NAME'] = '/' . basename($phpCandidate);
        $_SERVER['PHP_SELF'] = '/' . basename($phpCandidate);
        chdir(dirname($phpCandidate));
        require $phpCandidate;
        exit;
    }
}

// 4. Page 404
http_response_code(404);
echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'><title>404 - Non trouvé</title></head><body><h1>Erreur 404</h1><p>La page demandée est introuvable.</p><a href='/'>Retour à l'accueil</a></body></html>";
exit;
