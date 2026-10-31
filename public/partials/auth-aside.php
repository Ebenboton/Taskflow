<?php
// public/partials/auth-aside.php
// Panneau de présentation affiché à gauche des pages de connexion et d'inscription

require_once __DIR__ . '/icons.php';
?>
<aside class="auth-aside">
    <a href="login.php" class="auth-brand" aria-label="TaskFlow">
        <span class="logo-mark" aria-hidden="true"><?= icon('check', 18) ?></span>
        <span class="auth-brand-name">TaskFlow</span>
    </a>

    <div class="auth-pitch">
        <h2>Planifiez vos tâches, suivez votre avancement.</h2>
        <p>Un espace simple pour organiser votre travail au quotidien et ne plus rien laisser passer.</p>
        <ul class="auth-points">
            <li><?= icon('check', 24) ?><span>Priorités et échéances toujours visibles</span></li>
            <li><?= icon('check', 24) ?><span>Catégories de couleur pour tout classer</span></li>
            <li><?= icon('check', 24) ?><span>Export CSV pour Excel et Google Sheets</span></li>
        </ul>
    </div>

    <p class="auth-aside-foot">Gestion de tâches personnelle</p>
</aside>
