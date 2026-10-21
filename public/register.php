<?php
// public/register.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';

$auth = new Auth();

// Si déjà connecté → dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->register($username, $email, $password);

    if ($result['success']) {
        $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - TaskFlow</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <h1>Créer un compte</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <a href="login.php">Se connecter</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required minlength="3">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe (min. 6 caractères)</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>

        <p class="auth-link">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </p>
    </div>
</body>

</html>