<?php
// public/partials/header.php
// Barre de navigation commune à toutes les pages

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/CSRF.php';

$auth = $auth ?? new Auth();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="app-header">
    <div class="header-left">
        <a href="dashboard.php" class="logo-link">
            <h1 class="logo">TaskFlow</h1>
        </a>
    </div>

    <button type="button" class="nav-toggle" id="navToggle"
            aria-label="Ouvrir le menu de navigation" aria-controls="headerNav" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>

    <nav class="header-nav" id="headerNav">
        <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="tasks.php" class="<?= $currentPage === 'tasks.php' ? 'active' : '' ?>">Tâches</a>
        <a href="categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">Catégories</a>
    </nav>

    <div class="user-menu">
        <button type="button" class="user-menu-toggle" id="userMenuToggle" aria-label="Menu utilisateur">
            <span class="user-avatar">👤</span>
        </button>
        <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-header">
                <span class="user-dropdown-name"><?= htmlspecialchars($auth->getUsername()) ?></span>
            </div>
            <form method="POST" action="logout.php" style="margin:0;">
                <?= CSRF::field() ?>
                <button type="submit" class="user-dropdown-logout">
                    <span class="logout-icon">⏻</span>
                    <span class="logout-text">Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('userMenuToggle');
    const dropdown = document.getElementById('userDropdown');

    if (!toggle || !dropdown) return;

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('show');
    });
});
</script>