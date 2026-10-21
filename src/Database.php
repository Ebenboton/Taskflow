<?php
// src/Database.php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            $portPart = !empty($config['port']) ? ";port={$config['port']}" : '';
            $dsn = "mysql:host={$config['host']}{$portPart};dbname={$config['dbname']};charset={$config['charset']}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Configuration SSL pour les bases distantes (TiDB, Aiven, PlanetScale, etc.)
            if (!empty($config['ssl_ca']) && file_exists($config['ssl_ca'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
            } elseif (!empty($config['ssl'])) {
                // Active le chiffrement SSL
                if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                // En production, afficher un message propre sans exposer les mots de passe
                throw new Exception("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}