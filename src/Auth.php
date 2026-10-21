<?php
// src/Auth.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/session.php';  // ← Gère session_start()

class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Inscrit un nouvel utilisateur + crée ses 6 catégories par défaut
     */
    public function register(string $username, string $email, string $password): array
    {
        $username = trim($username);
        $email    = trim($email);

        if (strlen($username) < 3) {
            return ['success' => false, 'message' => "Le nom d'utilisateur doit faire au moins 3 caractères."];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "L'email n'est pas valide."];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => "Le mot de passe doit faire au moins 6 caractères."];
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "Impossible de créer le compte avec ces informations."];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $userId = (int) $this->db->lastInsertId();

            $this->createDefaultCategories($userId);

            $this->db->commit();

            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => "Erreur lors de l'inscription."];
        }
    }

    /**
     * Connecte un utilisateur
     */
    public function login(string $email, string $password): array
    {
        $email = trim($email);

        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => "Identifiants incorrects."];
        }

        // Régénération de l'ID de session après login (anti-fixation)
        session_regenerate_id(true);

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['__last_regeneration'] = time();

        return ['success' => true];
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(): void
    {
        $_SESSION = [];

        // Supprime aussi le cookie côté navigateur
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Retourne l'ID de l'utilisateur connecté
     */
    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Retourne le username
     */
    public function getUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Crée les 6 catégories par défaut
     */
    private function createDefaultCategories(int $userId): void
    {
        $defaults = [
            ['Personnel', '#3498db'],
            ['Travail',   '#e74c3c'],
            ['Études',    '#9b59b6'],
            ['Santé',     '#2ecc71'],
            ['Loisirs',   '#f39c12'],
            ['Urgent',    '#e67e22'],
        ];

        $stmt = $this->db->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");

        foreach ($defaults as [$name, $color]) {
            $stmt->execute([$userId, $name, $color]);
        }
    }
}