<?php
// src/LoginThrottle.php

require_once __DIR__ . '/Database.php';

class LoginThrottle
{
    private PDO $db;

    // Configuration
    public const MAX_ATTEMPTS = 5;          // Nombre d'échecs autorisés
    private const WINDOW_MINUTES = 15;      // Fenêtre de temps en minutes
    private const LOCKOUT_MINUTES = 15;     // Durée du blocage en minutes

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Vérifie si l'utilisateur (email + IP) est actuellement bloqué
     * Retourne ['blocked' => bool, 'remaining' => int (secondes)]
     *
     * Le calcul est fait directement en SQL pour éviter tout décalage de fuseau horaire.
     */
    public function isBlocked(string $email, string $ip): array
    {
        $sql = "SELECT COUNT(*) AS attempts,
                       TIMESTAMPDIFF(
                           SECOND,
                           NOW(),
                           DATE_ADD(MAX(attempted_at), INTERVAL ? MINUTE)
                       ) AS remaining_seconds
                FROM login_attempts
                WHERE email = ?
                  AND ip_address = ?
                  AND attempted_at > (NOW() - INTERVAL ? MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            self::LOCKOUT_MINUTES,
            $email,
            $ip,
            self::WINDOW_MINUTES,
        ]);

        $row = $stmt->fetch();

        if ((int) $row['attempts'] < self::MAX_ATTEMPTS) {
            return ['blocked' => false, 'remaining' => 0];
        }

        // remaining_seconds peut être négatif si le délai est écoulé
        $remaining = max(0, (int) $row['remaining_seconds']);

        // Si le temps est écoulé, on n'est plus bloqué
        if ($remaining === 0) {
            return ['blocked' => false, 'remaining' => 0];
        }

        return ['blocked' => true, 'remaining' => $remaining];
    }

    /**
     * Enregistre un échec de connexion
     */
    public function recordFailure(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)"
        );
        $stmt->execute([$email, $ip]);
    }

    /**
     * Efface les tentatives (après un login réussi)
     */
    public function clear(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM login_attempts WHERE email = ? AND ip_address = ?"
        );
        $stmt->execute([$email, $ip]);
    }

    /**
     * Nettoie les vieilles entrées (à appeler de temps en temps)
     */
    public function cleanup(): void
    {
        $this->db->exec(
            "DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)"
        );
    }

    /**
     * Formate le temps restant en texte lisible
     */
    public static function formatRemaining(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconde' . ($seconds > 1 ? 's' : '');
        }
        $minutes = (int) ceil($seconds / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}