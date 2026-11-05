<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
$workshops = require __DIR__ . '/../data/workshops.php';

if (!isset($_SESSION['bookings'])) {
    $_SESSION['bookings'] = [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$theme = in_array($_COOKIE['theme'] ?? '', ['light', 'dark'], true)
    ? $_COOKIE['theme'] : 'light';
