<?php
require __DIR__ . '/lib/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Method not allowed'); }
if (!csrf_valid($_POST)) { http_response_code(403); exit('Invalid CSRF token'); }

$choice = (string)($_POST['theme'] ?? '');
if (!in_array($choice, ['light','dark'], true)) { http_response_code(422); exit('Invalid theme'); }

setcookie('theme', $choice, [
    'expires'=>time()+30*86400, 'path'=>'/', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
    'httponly'=>true, 'samesite'=>'Lax'
]);
redirect_to('index.php');
