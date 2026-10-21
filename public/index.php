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
        <title>Configuration Requise - TaskFlow</title>
        <link rel="stylesheet" href="assets/css/base.css">
        <link rel="stylesheet" href="assets/css/components.css">
        <link rel="stylesheet" href="assets/css/auth.css">
    </head>
    <body class="auth-page">
        <div class="auth-card" style="max-width: 500px; text-align: center;">
            <h1 class="logo" style="margin-bottom: 1rem;">TaskFlow</h1>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; text-align: left;">
                <strong>⚠️ Connexion à la base de données impossible</strong><br>
                <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">
                Si vous déployez sur <strong>Vercel</strong>, assurez-vous d'avoir configuré les variables d'environnement dans les paramètres de votre projet Vercel (<code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASSWORD</code>, <code>DB_PORT</code>).
            </p>
            <a href="login.php" class="btn btn-primary" style="display: inline-block;">Réessayer</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}