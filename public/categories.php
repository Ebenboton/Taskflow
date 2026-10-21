<?php
// public/categories.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Category.php';
require_once __DIR__ . '/../src/CSRF.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getUserId();
$categoryModel = new Category();

$message = '';
$messageType = '';

// ===== Traitement des actions POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF obligatoire
    CSRF::checkOrFail();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $categoryModel->create($userId, $_POST['name'] ?? '', $_POST['color'] ?? '#3498db');
        $message = $result['success'] ? "Catégorie créée." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    if ($action === 'update') {
        $result = $categoryModel->update((int)($_POST['id'] ?? 0), $userId, $_POST['name'] ?? '', $_POST['color'] ?? '#3498db');
        $message = $result['success'] ? "Catégorie mise à jour." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    if ($action === 'delete') {
        $result = $categoryModel->delete((int)($_POST['id'] ?? 0), $userId);
        $message = $result['success'] ? "Catégorie supprimée." : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$categories = $categoryModel->getAll($userId);

$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = $categoryModel->getById((int) $_GET['edit'], $userId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - TaskFlow</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/categories.css">
</head>
<body class="dashboard-page">
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="app-main">
        <div class="page-header">
            <button onclick="history.back()" class="btn-back" title="Retour">←</button>
            <h2>Mes catégories</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h3><?= $editCategory ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h3>

            <form method="POST" action="" class="inline-form">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" required minlength="2" maxlength="50"
                           value="<?= $editCategory ? htmlspecialchars($editCategory['name']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="color">Couleur</label>
                    <input type="color" id="color" name="color"
                           value="<?= $editCategory ? htmlspecialchars($editCategory['color']) : '#3498db' ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editCategory ? 'Enregistrer' : 'Créer' ?>
                </button>

                <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="card">
            <h3>Liste (<?= count($categories) ?>)</h3>

            <?php if (empty($categories)): ?>
                <p>Aucune catégorie. Créez-en une !</p>
            <?php else: ?>
                <div class="category-grid">
                    <?php foreach ($categories as $cat): ?>
                        <div class="category-card" style="border-top: 4px solid <?= htmlspecialchars($cat['color']) ?>">
                            <div class="category-card-header">
                                <span class="color-dot" style="background: <?= htmlspecialchars($cat['color']) ?>"></span>
                                <h4><?= htmlspecialchars($cat['name']) ?></h4>
                            </div>

                            <p class="category-card-count">
                                <?= $cat['task_count'] ?> tâche<?= $cat['task_count'] > 1 ? 's' : '' ?>
                            </p>

                            <div class="category-card-actions">
                                <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-small">✏️</a>

                                <form method="POST" action="" style="display:inline"
                                      onsubmit="return confirm('Supprimer cette catégorie ? Les tâches liées passeront en Sans catégorie.');">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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