<?php
// public/dashboard.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Task.php';
require_once __DIR__ . '/../src/Category.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getUserId();
$username = $auth->getUsername();

$taskModel = new Task();
$categoryModel = new Category();

$stats = $taskModel->getStats($userId);
$urgentTasks = array_slice($taskModel->getAll($userId, ['status' => 'todo']), 0, 5);
$categories = $categoryModel->getAll($userId);

$total = (int) $stats['total'];
$done  = (int) $stats['done'];
$progress = $total > 0 ? round(($done / $total) * 100) : 0;

$hour = (int) date('H');
if ($hour < 12)      $greeting = "Bonjour";
elseif ($hour < 18)  $greeting = "Bon après-midi";
else                 $greeting = "Bonsoir";

// Calcul du périmètre du cercle de progression (r = 52)
$radius = 52;
$circumference = 2 * M_PI * $radius;
$dashOffset = $circumference - ($progress / 100) * $circumference;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TaskFlow</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body class="dashboard-page">
    <?php
    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/icons.php';
    ?>

    <main class="app-main app-main-wide">
        <!-- ===== En-tête de page ===== -->
        <div class="dashboard-hero">
            <div>
                <p class="hero-greeting"><?= $greeting ?>,</p>
                <h1 class="hero-name"><?= htmlspecialchars($username) ?></h1>
                <p class="hero-sub">Voici un aperçu de votre productivité aujourd'hui.</p>
            </div>
            <div class="hero-actions">
                <a href="tasks.php" class="btn btn-primary">
                    <?= icon('plus', 18) ?> Nouvelle tâche
                </a>
                <a href="export.php" class="btn btn-outline">
                    <?= icon('download', 18) ?> Exporter
                </a>
            </div>
        </div>

        <!-- ===== Grille principale ===== -->
        <div class="dashboard-grid">

            <!-- ===== Colonne gauche ===== -->
            <div class="dashboard-col-main">

                <!-- Stats -->
                <section class="stats-grid-modern">
                    <div class="stat-card-modern stat-accent-blue">
                        <div class="stat-icon-wrap stat-icon-blue"><?= icon('inbox', 22) ?></div>
                        <div class="stat-body">
                            <span class="stat-value-modern"><?= $stats['total'] ?></span>
                            <span class="stat-label-modern">Tâches totales</span>
                        </div>
                    </div>

                    <div class="stat-card-modern stat-accent-amber">
                        <div class="stat-icon-wrap stat-icon-amber"><?= icon('clock', 22) ?></div>
                        <div class="stat-body">
                            <span class="stat-value-modern"><?= $stats['todo'] ?></span>
                            <span class="stat-label-modern">À faire</span>
                        </div>
                    </div>

                    <div class="stat-card-modern stat-accent-purple">
                        <div class="stat-icon-wrap stat-icon-purple"><?= icon('loader', 22) ?></div>
                        <div class="stat-body">
                            <span class="stat-value-modern"><?= $stats['in_progress'] ?></span>
                            <span class="stat-label-modern">En cours</span>
                        </div>
                    </div>

                    <div class="stat-card-modern stat-accent-green">
                        <div class="stat-icon-wrap stat-icon-green"><?= icon('check-circle', 22) ?></div>
                        <div class="stat-body">
                            <span class="stat-value-modern"><?= $stats['done'] ?></span>
                            <span class="stat-label-modern">Terminées</span>
                        </div>
                    </div>

                    <div class="stat-card-modern stat-accent-red">
                        <div class="stat-icon-wrap stat-icon-red"><?= icon('alert-triangle', 22) ?></div>
                        <div class="stat-body">
                            <span class="stat-value-modern"><?= $stats['overdue'] ?></span>
                            <span class="stat-label-modern">En retard</span>
                        </div>
                    </div>
                </section>

                <!-- Tâches à faire -->
                <section class="card-modern">
                    <div class="card-modern-header">
                        <div>
                            <h3 class="card-modern-title">À faire prochainement</h3>
                            <p class="card-modern-sub">Vos 5 prochaines tâches prioritaires</p>
                        </div>
                        <a href="tasks.php" class="btn btn-ghost btn-small">Voir tout →</a>
                    </div>

                    <?php if (empty($urgentTasks)): ?>
                        <div class="empty-modern">
                            <div class="empty-modern-icon"><?= icon('sparkles', 32) ?></div>
                            <p>Aucune tâche en attente</p>
                            <span class="empty-modern-sub">Profitez de votre temps libre !</span>
                        </div>
                    <?php else: ?>
                        <ul class="task-mini-list">
                            <?php foreach ($urgentTasks as $task): ?>
                                <?php
                                $overdue = $task['due_date']
                                    && strtotime($task['due_date']) < strtotime(date('Y-m-d'));
                                $priorityClass = 'priority-' . $task['priority'];
                                ?>
                                <li class="task-mini-item">
                                    <span class="task-mini-dot" style="background: <?= htmlspecialchars($task['category_color'] ?? '#95a5a6') ?>"></span>
                                    <div class="task-mini-body">
                                        <span class="task-mini-title"><?= htmlspecialchars($task['title']) ?></span>
                                        <?php if ($task['due_date']): ?>
                                            <span class="task-mini-date <?= $overdue ? 'is-overdue' : '' ?>">
                                                <?= icon('calendar', 13) ?>
                                                <?= date('d M', strtotime($task['due_date'])) ?>
                                                <?= $overdue ? '· En retard' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="priority-pill <?= $priorityClass ?>">
                                        <?= match ($task['priority']) {
                                            'low'    => 'Basse',
                                            'high'   => 'Haute',
                                            default  => 'Moyenne',
                                        } ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <!-- Catégories -->
                <section class="card-modern">
                    <div class="card-modern-header">
                        <div>
                            <h3 class="card-modern-title">Mes catégories</h3>
                            <p class="card-modern-sub">Répartition de vos tâches</p>
                        </div>
                        <a href="categories.php" class="btn btn-ghost btn-small">Gérer →</a>
                    </div>

                    <?php if (empty($categories)): ?>
                        <p class="empty-modern-sub">Aucune catégorie pour le moment.</p>
                    <?php else: ?>
                        <div class="category-chips">
                            <?php foreach ($categories as $cat): ?>
                                <div class="category-chip">
                                    <span class="category-chip-dot" style="background: <?= htmlspecialchars($cat['color']) ?>"></span>
                                    <span class="category-chip-name"><?= htmlspecialchars($cat['name']) ?></span>
                                    <span class="category-chip-count"><?= $cat['task_count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- ===== Colonne droite (sidebar) ===== -->
            <aside class="dashboard-col-side">

                <!-- Progression circulaire -->
                <section class="card-modern card-progress">
                    <h3 class="card-modern-title">Progression</h3>
                    <p class="card-modern-sub">Tâches terminées</p>

                    <div class="progress-circle-wrap">
                        <svg class="progress-circle" viewBox="0 0 120 120" width="160" height="160">
                            <circle class="progress-circle-bg" cx="60" cy="60" r="<?= $radius ?>" />
                            <circle class="progress-circle-fill" cx="60" cy="60" r="<?= $radius ?>"
                                stroke-dasharray="<?= $circumference ?>"
                                stroke-dashoffset="<?= $dashOffset ?>" />
                        </svg>
                        <div class="progress-circle-label">
                            <span class="progress-percent"><?= $progress ?>%</span>
                            <span class="progress-fraction"><?= $done ?> / <?= $total ?></span>
                        </div>
                    </div>

                    <div class="progress-footer">
                        <?= icon('trending-up', 16) ?>
                        <span><?= $total - $done ?> tâche<?= ($total - $done) > 1 ? 's' : '' ?> restante<?= ($total - $done) > 1 ? 's' : '' ?></span>
                    </div>
                </section>

                <!-- Actions rapides -->
                <section class="card-modern">
                    <h3 class="card-modern-title">Actions rapides</h3>

                    <div class="quick-actions-modern">
                        <a href="tasks.php" class="quick-action">
                            <div class="quick-action-icon quick-icon-blue"><?= icon('plus', 20) ?></div>
                            <div class="quick-action-body">
                                <span class="quick-action-title">Nouvelle tâche</span>
                                <span class="quick-action-sub">Créer rapidement</span>
                            </div>
                        </a>

                        <a href="categories.php" class="quick-action">
                            <div class="quick-action-icon quick-icon-purple"><?= icon('folder', 20) ?></div>
                            <div class="quick-action-body">
                                <span class="quick-action-title">Catégories</span>
                                <span class="quick-action-sub">Organiser</span>
                            </div>
                        </a>

                        <a href="export.php" class="quick-action">
                            <div class="quick-action-icon quick-icon-green"><?= icon('download', 20) ?></div>
                            <div class="quick-action-body">
                                <span class="quick-action-title">Export CSV</span>
                                <span class="quick-action-sub">Sauvegarder</span>
                            </div>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</body>

</html>