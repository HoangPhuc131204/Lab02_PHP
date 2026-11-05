<?php
require __DIR__ . '/lib/bootstrap.php';

$bookings = $_SESSION['bookings'];
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My bookings</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<header>
<h1>My Bookings</h1>
<nav>
<a href="index.php">Catalog</a>
<a href="dashboard.php">My bookings</a>
</nav>
</header>
<?php if ($flash): ?>
<p class="notice">
<?= e($flash) ?>
</p>
<?php endif; ?>
<?php if (!$bookings): ?>
<p class="panel">No bookings yet. <a href="index.php">Choose a workshop</a>.</p>
<?php else: ?>
<div class="panel">
<table>
<thead>
<tr>
<th>Reference</th>
<th>Attendee</th>
<th>Workshop</th>
<th>Qty</th>
<th>Amount</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
<td>
<?= e($b['ref']) ?>
</td>
<td>
<?= e($b['full_name']) ?>
<br>
<small>
<?= e($b['email']) ?>
</small>
</td>
<td>
<?= e($b['workshop']) ?>
</td>
<td>
<?= e($b['quantity']) ?>
</td>
<td>
<?= number_format($b['amount']) ?> VND</td>
<td class="actions">
<!-- Lab 02: both are plain GET links; ref is URL-encoded -->
<a class="btn btn-sm" href="booking_detail.php?ref=<?= urlencode($b['ref']) ?>">View Details</a>
<a class="btn btn-sm secondary" href="booking_edit.php?ref=<?= urlencode($b['ref']) ?>">Edit</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
<form method="post" action="logout.php">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<button class="danger">Clear session &amp; start over</button>
</form>
</main>
</body>
</html>
