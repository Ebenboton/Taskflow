<?php
// src/Category.php

require_once __DIR__ . '/Database.php';

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère toutes les catégories d'un utilisateur
     * avec le nombre de tâches associées
     */
    public function getAll(int $userId): array
    {
        $sql = "SELECT c.id, c.name, c.color, c.created_at,
                       COUNT(t.id) AS task_count
                FROM categories c
                LEFT JOIN tasks t ON t.category_id = c.id
                WHERE c.user_id = ?
                GROUP BY c.id
                ORDER BY c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère une catégorie par son ID (en vérifiant qu'elle appartient à l'user)
     */
    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    /**
     * Crée une nouvelle catégorie
     */
    public function create(int $userId, string $name, string $color = '#3498db'): array
    {
        $name = trim($name);

        if (strlen($name) < 2) {
            return ['success' => false, 'message' => "Le nom doit faire au moins 2 caractères."];
        }

        if (strlen($name) > 50) {
            return ['success' => false, 'message' => "Le nom ne doit pas dépasser 50 caractères."];
        }

        // Valider la couleur (format hexadécimal #RRGGBB)
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#3498db';
        }

        // Vérifier qu'une catégorie du même nom n'existe pas déjà pour cet user
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
        $stmt->execute([$userId, $name]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "Vous avez déjà une catégorie avec ce nom."];
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $name, $color]);
            return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la création."];
        }
    }

    /**
     * Met à jour une catégorie
     */
    public function update(int $id, int $userId, string $name, string $color): array
    {
        $name = trim($name);

        if (strlen($name) < 2 || strlen($name) > 50) {
            return ['success' => false, 'message' => "Le nom doit faire entre 2 et 50 caractères."];
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#3498db';
        }

        // Vérifier que la catégorie appartient bien à l'utilisateur
        if (!$this->getById($id, $userId)) {
            return ['success' => false, 'message' => "Catégorie introuvable."];
        }

        // Vérifier qu'aucune autre catégorie du même user n'a ce nom
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND id != ?");
        $stmt->execute([$userId, $name, $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "Une autre catégorie porte déjà ce nom."];
        }

        try {
            $stmt = $this->db->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $color, $id, $userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la mise à jour."];
        }
    }

    /**
     * Supprime une catégorie
     * Les tâches liées passent en "Sans catégorie" (grâce à ON DELETE SET NULL)
     */
    public function delete(int $id, int $userId): array
    {
        if (!$this->getById($id, $userId)) {
            return ['success' => false, 'message' => "Catégorie introuvable."];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la suppression."];
        }
    }
}