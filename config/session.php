<?php
// config/session.php
// Configure la session AVANT session_start()
// À inclure en TOUT PREMIER dans chaque page qui utilise la session.

if (session_status() === PHP_SESSION_NONE) {

    // Détecte si on est en HTTPS
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https')
    );

    // Options du cookie de session
    $cookieParams = [
        'lifetime' => 0,            // expire à la fermeture du navigateur
        'path'     => '/',          // accessible sur tout le site
        'domain'   => '',           // domaine courant
        'secure'   => $isHttps,     // true en HTTPS, false en HTTP local
        'httponly' => true,         // inaccessible en JavaScript
        'samesite' => 'Lax',        // protection CSRF niveau navigateur
    ];

    session_set_cookie_params($cookieParams);

    // Nom de session custom (au lieu de PHPSESSID)
    session_name('TASKFLOW_SESS');

    // Mode strict : refuse les IDs de session non initialisés par PHP
    ini_set('session.use_strict_mode', '1');

    // Cookie uniquement (pas de transmission par URL)
    ini_set('session.use_only_cookies', '1');

    // Durée de vie du garbage collector : 2 heures
    ini_set('session.gc_maxlifetime', '7200');

    // Régénère l'ID de session périodiquement (toutes les 30 min)
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    session_start();

    // Régénération périodique de l'ID (anti-fixation long terme)
    if (!isset($_SESSION['__last_regeneration'])) {
        $_SESSION['__last_regeneration'] = time();
    } elseif (time() - $_SESSION['__last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['__last_regeneration'] = time();
    }
}