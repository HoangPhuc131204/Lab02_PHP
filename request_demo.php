<?php
require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$getName = (string)($_GET['name'] ?? '');
$postName = (string)($_POST['name'] ?? '');
// $_REQUEST may combine GET, POST, and COOKIE based on PHP settings; do not trust for business logic.
$requestName = (string)($_REQUEST['name'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Request Inspector</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<h1>Request Inspector</h1>
<p>Method: <?= e($method) ?>
</p>
<p>GET name: <?= e($getName) ?>
</p>
<p>POST name: <?= e($postName) ?>
</p>
<p>REQUEST name: <?= e($requestName) ?>
</p>
<form method="get">
<input name="name" placeholder="GET name">
<button>Send GET</button>
</form>
<form method="post">
<input name="name" placeholder="POST name">
<button>Send POST</button>
</form>
<a href="index.php">Back to catalog</a>
</main>
</body>
</html>
