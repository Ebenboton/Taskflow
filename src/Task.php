<?php
// src/Task.php

require_once __DIR__ . '/Database.php';

class Task
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère toutes les tâches d'un utilisateur
     * avec filtres optionnels : catégorie, statut, priorité, recherche
     */
    public function getAll(int $userId, array $filters = []): array
    {
        $sql = "SELECT t.id, t.title, t.description, t.due_date, t.status, t.priority,
                       t.created_at, t.updated_at,
                       t.category_id, c.name AS category_name, c.color AS category_color
                FROM tasks t
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.user_id = ?";
        $params = [$userId];

        // Filtre par catégorie
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = $filters['category_id'];
        }

        // Filtre par statut
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        // Filtre par priorité
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Recherche par titre ou description
        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        // Tri : tâches non terminées en premier, puis par échéance, puis par date de création
        $sql .= " ORDER BY 
                    FIELD(t.status, 'todo', 'in_progress', 'done'),
                    t.due_date IS NULL,
                    t.due_date ASC,
                    t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Récupère une tâche par son ID (vérifie qu'elle appartient à l'user)
     */
    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $task = $stmt->fetch();
        return $task ?: null;
    }

    /**
     * Crée une nouvelle tâche
     */
    public function create(int $userId, array $data): array
    {
        $title = trim($data['title'] ?? '');
        if (strlen($title) < 2 || strlen($title) > 150) {
            return ['success' => false, 'message' => "Le titre doit faire entre 2 et 150 caractères."];
        }

        $description = trim($data['description'] ?? '');
        $due_date    = !empty($data['due_date']) ? $data['due_date'] : null;
        $priority    = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $status      = in_array($data['status'] ?? '', ['todo', 'in_progress', 'done']) ? $data['status'] : 'todo';
        $category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;

        // Vérifier que la catégorie appartient bien à l'user (si fournie)
        if ($category_id !== null) {
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$category_id, $userId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => "Catégorie invalide."];
            }
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO tasks (user_id, category_id, title, description, due_date, status, priority)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $category_id, $title, $description, $due_date, $status, $priority]);
            return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la création."];
        }
    }

    /**
     * Met à jour une tâche
     */
    public function update(int $id, int $userId, array $data): array
    {
        if (!$this->getById($id, $userId)) {
            return ['success' => false, 'message' => "Tâche introuvable."];
        }

        $title = trim($data['title'] ?? '');
        if (strlen($title) < 2 || strlen($title) > 150) {
            return ['success' => false, 'message' => "Le titre doit faire entre 2 et 150 caractères."];
        }

        $description = trim($data['description'] ?? '');
        $due_date    = !empty($data['due_date']) ? $data['due_date'] : null;
        $priority    = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $status      = in_array($data['status'] ?? '', ['todo', 'in_progress', 'done']) ? $data['status'] : 'todo';
        $category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;

        if ($category_id !== null) {
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$category_id, $userId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => "Catégorie invalide."];
            }
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE tasks
                 SET category_id = ?, title = ?, description = ?, due_date = ?, status = ?, priority = ?
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$category_id, $title, $description, $due_date, $status, $priority, $id, $userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la mise à jour."];
        }
    }

    /**
     * Supprime une tâche
     */
    public function delete(int $id, int $userId): array
    {
        if (!$this->getById($id, $userId)) {
            return ['success' => false, 'message' => "Tâche introuvable."];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la suppression."];
        }
    }

    /**
     * Change rapidement le statut d'une tâche (utile pour une checkbox)
     */
    public function toggleStatus(int $id, int $userId): array
    {
        $task = $this->getById($id, $userId);
        if (!$task) {
            return ['success' => false, 'message' => "Tâche introuvable."];
        }

        $newStatus = $task['status'] === 'done' ? 'todo' : 'done';

        try {
            $stmt = $this->db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newStatus, $id, $userId]);
            return ['success' => true, 'status' => $newStatus];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors du changement de statut."];
        }
    }

    /**
     * Statistiques pour le dashboard
     */
    public function getStats(int $userId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) AS todo,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done,
                    SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) AS overdue
                FROM tasks
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [
            'total' => 0, 'todo' => 0, 'in_progress' => 0, 'done' => 0, 'overdue' => 0
        ];
    }
}