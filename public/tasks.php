<?php
// public/tasks.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Task.php';
require_once __DIR__ . '/../src/Category.php';
require_once __DIR__ . '/../src/CSRF.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getUserId();
$taskModel = new Task();
$categoryModel = new Category();

$message = '';
$messageType = '';

// ===== Traitement des actions POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::checkOrFail();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $taskModel->create($userId, $_POST);
        $message = $result['success'] ? "Tâche créée." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    if ($action === 'update') {
        $result = $taskModel->update((int)($_POST['id'] ?? 0), $userId, $_POST);
        $message = $result['success'] ? "Tâche mise à jour." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    if ($action === 'delete') {
        $result = $taskModel->delete((int)($_POST['id'] ?? 0), $userId);
        $message = $result['success'] ? "Tâche supprimée." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    if ($action === 'toggle') {
        $result = $taskModel->toggleStatus((int)($_POST['id'] ?? 0), $userId);
        if (!$result['success']) {
            $message = $result['message'];
            $messageType = 'error';
        }
    }

    if (in_array($action, ['create', 'update', 'delete', 'toggle']) && $messageType !== 'error') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $messageType;
        header('Location: tasks.php');
        exit;
    }
}

if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

$filters = [
    'category_id' => !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null,
    'status'      => $_GET['status'] ?? '',
    'priority'    => $_GET['priority'] ?? '',
    'search'      => trim($_GET['search'] ?? ''),
];

$tasks = $taskModel->getAll($userId, array_filter($filters));
$categories = $categoryModel->getAll($userId);

$editTask = null;
if (isset($_GET['edit'])) {
    $editTask = $taskModel->getById((int) $_GET['edit'], $userId);
}

function statusLabel(string $status): string {
    return match($status) {
        'todo'        => 'À faire',
        'in_progress' => 'En cours',
        'done'        => 'Terminée',
        default       => $status,
    };
}

function priorityLabel(string $priority): string {
    return match($priority) {
        'low'    => 'Basse',
        'medium' => 'Moyenne',
        'high'   => 'Haute',
        default  => $priority,
    };
}

function isOverdue(?string $dueDate, string $status): bool {
    return $dueDate !== null && $status !== 'done' && strtotime($dueDate) < strtotime(date('Y-m-d'));
}

$exportQuery = http_build_query(array_filter([
    'category_id' => $filters['category_id'],
    'status'      => $filters['status'],
    'priority'    => $filters['priority'],
    'search'      => $filters['search'],
]));
$exportUrl = 'export.php' . ($exportQuery ? '?' . $exportQuery : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tâches - TaskFlow</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/tasks.css">
</head>
<body class="dashboard-page">
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="app-main">
        <div class="page-header">
            <button onclick="history.back()" class="btn-back" title="Retour">←</button>
            <h2>Mes tâches (<?= count($tasks) ?>)</h2>
            <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary btn-export">
                📥 Exporter CSV
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h3><?= $editTask ? 'Modifier la tâche' : 'Nouvelle tâche' ?></h3>

            <form method="POST" action="" class="task-form">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="<?= $editTask ? 'update' : 'create' ?>">
                <?php if ($editTask): ?>
                    <input type="hidden" name="id" value="<?= $editTask['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Titre *</label>
                        <input type="text" id="title" name="title" required minlength="2" maxlength="150"
                               value="<?= $editTask ? htmlspecialchars($editTask['title']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Catégorie</label>
                        <select id="category_id" name="category_id">
                            <option value="">— Sans catégorie —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= ($editTask && $editTask['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= $editTask ? htmlspecialchars($editTask['description']) : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Échéance</label>
                        <input type="date" id="due_date" name="due_date"
                               value="<?= $editTask && $editTask['due_date'] ? htmlspecialchars($editTask['due_date']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="priority">Priorité</label>
                        <select id="priority" name="priority">
                            <option value="low"    <?= ($editTask && $editTask['priority'] === 'low')    ? 'selected' : '' ?>>Basse</option>
                            <option value="medium" <?= (!$editTask || $editTask['priority'] === 'medium') ? 'selected' : '' ?>>Moyenne</option>
                            <option value="high"   <?= ($editTask && $editTask['priority'] === 'high')   ? 'selected' : '' ?>>Haute</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="todo"        <?= (!$editTask || $editTask['status'] === 'todo')        ? 'selected' : '' ?>>À faire</option>
                            <option value="in_progress" <?= ($editTask && $editTask['status'] === 'in_progress') ? 'selected' : '' ?>>En cours</option>
                            <option value="done"        <?= ($editTask && $editTask['status'] === 'done')        ? 'selected' : '' ?>>Terminée</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editTask ? 'Enregistrer' : 'Créer la tâche' ?>
                    </button>
                    <?php if ($editTask): ?>
                        <a href="tasks.php" class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h3>Filtres</h3>
            <form method="GET" action="" class="filters-form">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" placeholder="Titre ou description..."
                           value="<?= htmlspecialchars($filters['search']) ?>">
                </div>

                <div class="form-group">
                    <label for="filter_category">Catégorie</label>
                    <select id="filter_category" name="category_id">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filter_status">Statut</label>
                    <select id="filter_status" name="status">
                        <option value="">Tous</option>
                        <option value="todo"        <?= $filters['status'] === 'todo'        ? 'selected' : '' ?>>À faire</option>
                        <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                        <option value="done"        <?= $filters['status'] === 'done'        ? 'selected' : '' ?>>Terminée</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filter_priority">Priorité</label>
                    <select id="filter_priority" name="priority">
                        <option value="">Toutes</option>
                        <option value="low"    <?= $filters['priority'] === 'low'    ? 'selected' : '' ?>>Basse</option>
                        <option value="medium" <?= $filters['priority'] === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="high"   <?= $filters['priority'] === 'high'   ? 'selected' : '' ?>>Haute</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="tasks.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h3>Liste</h3>

            <?php if (empty($tasks)): ?>
                <p>Aucune tâche trouvée.</p>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <?php
                            $overdue = isOverdue($task['due_date'], $task['status']);
                            $borderColor = $task['category_color'] ?? '#95a5a6';
                        ?>
                        <div class="task-card <?= $task['status'] === 'done' ? 'task-done' : '' ?>"
                             style="border-left: 4px solid <?= htmlspecialchars($borderColor) ?>">

                            <form method="POST" action="" class="task-toggle-form">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                <button type="submit" class="task-toggle" title="Marquer comme <?= $task['status'] === 'done' ? 'à faire' : 'terminée' ?>">
                                    <?= $task['status'] === 'done' ? '☑' : '☐' ?>
                                </button>
                            </form>

                            <div class="task-content">
                                <div class="task-header">
                                    <h4><?= htmlspecialchars($task['title']) ?></h4>

                                    <div class="task-badges">
                                        <?php if ($task['category_name']): ?>
                                            <span class="badge" style="background: <?= htmlspecialchars($task['category_color']) ?>20; color: <?= htmlspecialchars($task['category_color']) ?>; border: 1px solid <?= htmlspecialchars($task['category_color']) ?>40">
                                                <?= htmlspecialchars($task['category_name']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="badge badge-status-<?= $task['status'] ?>">
                                            <?= statusLabel($task['status']) ?>
                                        </span>

                                        <span class="badge badge-priority-<?= $task['priority'] ?>">
                                            <?= priorityLabel($task['priority']) ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($task['description']): ?>
                                    <p class="task-description"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                                <?php endif; ?>

                                <?php if ($task['due_date']): ?>
                                    <p class="task-due <?= $overdue ? 'task-overdue' : '' ?>">
                                        📅 <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                                        <?= $overdue ? ' — En retard !' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="task-actions">
                                <a href="tasks.php?edit=<?= $task['id'] ?>" class="btn btn-small">✏️</a>
                                <form method="POST" action="" style="display:inline"
                                      onsubmit="return confirm('Supprimer cette tâche ?');">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-white">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>