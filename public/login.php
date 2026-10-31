<?php
// public/login.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/LoginThrottle.php';
require_once __DIR__ . '/partials/icons.php';

$auth = new Auth();
$throttle = new LoginThrottle();

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// Récupère l'IP du client
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Vérifier si bloqué
    $check = $throttle->isBlocked($email, $ip);

    if ($check['blocked']) {
        $remaining = LoginThrottle::formatRemaining($check['remaining']);
        $error = "Trop de tentatives échouées. Réessayez dans {$remaining}.";
    } else {
        // 2. Tenter la connexion
        $result = $auth->login($email, $password);

        if ($result['success']) {
            // Succès → effacer les tentatives
            $throttle->clear($email, $ip);
            header('Location: dashboard.php');
            exit;
        } else {
            // Échec → enregistrer
            $throttle->recordFailure($email, $ip);

            // Re-vérifier pour voir si on vient de bloquer
            $check = $throttle->isBlocked($email, $ip);

            if ($check['blocked']) {
                $remaining = LoginThrottle::formatRemaining($check['remaining']);
                $error = "Trop de tentatives échouées. Compte bloqué pendant {$remaining}.";
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }
    }
}

// Nettoyage occasionnel (1 chance sur 10)
if (random_int(1, 10) === 1) {
    $throttle->cleanup();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TaskFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Instrument+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <?php require __DIR__ . '/partials/auth-aside.php'; ?>

        <main class="auth-main">
            <div class="auth-container">
                <h1>Connexion</h1>
                <p class="auth-lead">Accédez à vos tâches et à votre tableau de bord.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <?= icon('alert-circle', 18) ?>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                               value="<?= htmlspecialchars($email) ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </form>

                <p class="auth-link">
                    Pas encore de compte ? <a href="register.php">S'inscrire</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>