<?php
// public/export.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Task.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getUserId();
$taskModel = new Task();

// ===== Filtres optionnels (mêmes que tasks.php) =====
$filters = [
    'category_id' => !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null,
    'status'      => $_GET['status'] ?? '',
    'priority'    => $_GET['priority'] ?? '',
    'search'      => trim($_GET['search'] ?? ''),
];

$tasks = $taskModel->getAll($userId, array_filter($filters));

// ===== Nom du fichier =====
$filename = 'taskflow_export_' . date('Y-m-d_His') . '.csv';

// ===== Headers HTTP pour forcer le téléchargement =====
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// ===== Ouvrir la sortie standard en écriture =====
$output = fopen('php://output', 'w');

// ===== BOM UTF-8 pour qu'Excel reconnaisse les accents =====
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// ===== En-têtes de colonnes =====
fputcsv($output, [
    'ID',
    'Titre',
    'Description',
    'Catégorie',
    'Statut',
    'Priorité',
    'Échéance',
    'Créée le',
    'Mise à jour le',
], ';');

// ===== Traduction des valeurs =====
$statusLabels = [
    'todo'        => 'À faire',
    'in_progress' => 'En cours',
    'done'        => 'Terminée',
];

$priorityLabels = [
    'low'    => 'Basse',
    'medium' => 'Moyenne',
    'high'   => 'Haute',
];

// ===== Lignes de données =====
foreach ($tasks as $task) {
    fputcsv($output, [
        $task['id'],
        $task['title'],
        $task['description'] ?? '',
        $task['category_name'] ?? 'Sans catégorie',
        $statusLabels[$task['status']] ?? $task['status'],
        $priorityLabels[$task['priority']] ?? $task['priority'],
        $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : '',
        date('d/m/Y H:i', strtotime($task['created_at'])),
        date('d/m/Y H:i', strtotime($task['updated_at'])),
    ], ';');
}

fclose($output);
exit;