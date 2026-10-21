<?php
// public/logout.php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/CSRF.php';

// La déconnexion doit être en POST + CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

CSRF::checkOrFail();

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;