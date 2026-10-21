<?php
// src/CSRF.php

require_once __DIR__ . '/../config/session.php';

class CSRF
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Génère (ou récupère) le token CSRF de la session
     */
    public static function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Retourne le champ caché HTML à insérer dans les formulaires
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Vérifie que le token reçu correspond à celui de la session
     */
    public static function validate(?string $token): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Vérifie le token ou coupe l'exécution
     */
    public static function checkOrFail(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!self::validate($token)) {
            http_response_code(403);
            die('Erreur de sécurité : token CSRF invalide. Veuillez recharger la page.');
        }
    }
}