<?php
// public/index.php

require_once __DIR__ . '/../src/Database.php';

try {
    $pdo = Database::getConnection();
    echo "✅ Connexion à la base de données réussie !";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}