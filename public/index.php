<?php
// public/index.php

require_once __DIR__ . '/../src/Auth.php';

try {
    $auth = new Auth();
    if ($auth->isLoggedIn()) {
        header('Location: dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
} catch (Throwable $e) {
    // Si la base de données n'est pas encore connectée ou configurée sur Vercel
    ?>
<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configuration requise - TaskFlow</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Instrument+Sans:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="assets/css/base.css">
        <link rel="stylesheet" href="assets/css/components.css">
        <link rel="stylesheet" href="assets/css/auth.css">
    </head>
    <body class="auth-solo">
        <div class="auth-card">
            <span class="logo">TaskFlow</span>
            <div class="alert alert-danger" role="alert">
                <span>
                    <strong>Connexion à la base de données impossible</strong><br>
                    <?= htmlspecialchars($e->getMessage()) ?>
                </span>
            </div>
            <p>
                Si vous déployez sur <strong>Vercel</strong>, assurez-vous d'avoir configuré les variables d'environnement dans les paramètres de votre projet Vercel (<code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASSWORD</code>, <code>DB_PORT</code>).
            </p>
            <a href="login.php" class="btn btn-primary">Réessayer</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}