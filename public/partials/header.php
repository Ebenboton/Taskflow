<?php
// public/partials/header.php
// Navigation commune à toutes les pages (barre latérale sur ordinateur, barre supérieure sur mobile)

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/CSRF.php';
require_once __DIR__ . '/icons.php';

$auth = $auth ?? new Auth();
$currentPage = basename($_SERVER['PHP_SELF']);
$headerUsername = (string) $auth->getUsername();
$headerInitial = mb_strtoupper(mb_substr($headerUsername, 0, 1)) ?: '?';
?>
<header class="app-header" id="appHeader">
    <div class="header-bar">
        <a href="dashboard.php" class="logo-link" aria-label="TaskFlow – Tableau de bord">
            <span class="logo-mark" aria-hidden="true"><?= icon('check', 18) ?></span>
            <span class="logo">TaskFlow</span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle"
                aria-label="Ouvrir le menu de navigation" aria-controls="headerPanel" aria-expanded="false">
            <?= icon('menu', 22) ?>
        </button>
    </div>

    <div class="header-panel" id="headerPanel">
        <nav class="header-nav" id="headerNav" aria-label="Navigation principale">
            <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"<?= $currentPage === 'dashboard.php' ? ' aria-current="page"' : '' ?>>
                <?= icon('grid', 18) ?><span>Tableau de bord</span>
            </a>
            <a href="tasks.php" class="<?= $currentPage === 'tasks.php' ? 'active' : '' ?>"<?= $currentPage === 'tasks.php' ? ' aria-current="page"' : '' ?>>
                <?= icon('check-square', 18) ?><span>Tâches</span>
            </a>
            <a href="categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>"<?= $currentPage === 'categories.php' ? ' aria-current="page"' : '' ?>>
                <?= icon('folder', 18) ?><span>Catégories</span>
            </a>
        </nav>

        <div class="user-menu">
            <div class="user-identity">
                <span class="user-avatar" aria-hidden="true"><?= htmlspecialchars($headerInitial) ?></span>
                <span class="user-name"><?= htmlspecialchars($headerUsername) ?></span>
            </div>
            <form method="POST" action="logout.php" class="logout-form">
                <?= CSRF::field() ?>
                <button type="submit" class="user-dropdown-logout">
                    <?= icon('log-out', 16) ?>
                    <span class="logout-text">Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var header = document.getElementById('appHeader');
    var toggle = document.getElementById('navToggle');
    if (!header || !toggle) return;

    function setOpen(open) {
        header.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Fermer le menu de navigation' : 'Ouvrir le menu de navigation');
    }

    toggle.addEventListener('click', function () {
        setOpen(!header.classList.contains('is-open'));
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
});
</script>
