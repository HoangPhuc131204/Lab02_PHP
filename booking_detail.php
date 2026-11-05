<?php
/**
 * Lab 02 – Feature A: View Booking Detail (read-only, GET).
 * Rules: D01 valid ref | D02 missing/unknown ref | D03 escape output
 *        D04 navigation | D05 no mutation of $_SESSION
 */
require __DIR__ . '/lib/bootstrap.php';

// Only GET/HEAD may view. Nothing in this file writes to $_SESSION['bookings'].
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    exit('Method not allowed');
}

$ref     = ref_from_query();                                   // D01/D02: safe read of ?ref=
$index   = find_booking_index($_SESSION['bookings'], $ref);    // null when missing / unknown
$booking = $index === null ? null : $_SESSION['bookings'][$index];

if ($booking === null) {
    http_response_code(404);                                   // D02
} else {
    // Unit fee comes from the trusted catalog, not from the booking row or the client.
    $catalogItem = $workshops[$booking['workshop_id'] ?? ''] ?? null;
    $unitFee     = $catalogItem['fee'] ?? ($booking['quantity'] > 0 ? intdiv((int)$booking['amount'], (int)$booking['quantity']) : 0);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Booking detail</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<header>
<h1>Booking Detail</h1>
<nav>
<a href="index.php">Catalog</a>
<a href="dashboard.php">My bookings</a>
</nav>
</header>

<?php if ($booking === null): ?>
<div class="panel">
<p class="notice warn"><strong>Booking not found.</strong> The reference
<?php if ($ref !== ''): ?>
<code><?= e($ref) ?></code>
<?php else: ?>
was not provided
<?php endif; ?>
does not match any booking in your current session.</p>
<a class="btn" href="dashboard.php">Back to My Bookings</a>
</div>

<?php else: ?>
<div class="panel">
<dl class="detail">
<dt>Reference</dt><dd><?= e($booking['ref']) ?></dd>
<dt>Full name</dt><dd><?= e($booking['full_name']) ?></dd>
<dt>Email</dt><dd><?= e($booking['email']) ?></dd>
<dt>Workshop</dt><dd><?= e($booking['workshop']) ?></dd>
<dt>Quantity</dt><dd><?= e($booking['quantity']) ?> ticket(s)</dd>
<dt>Unit fee</dt><dd><?= number_format((int)$unitFee) ?> VND</dd>
<dt>Amount</dt><dd><strong><?= number_format((int)$booking['amount']) ?> VND</strong></dd>
</dl>
<div class="actions-bar">
<a class="btn secondary" href="dashboard.php">Back to My Bookings</a>
<a class="btn" href="booking_edit.php?ref=<?= urlencode($booking['ref']) ?>">Edit This Booking</a>
</div>
</div>
<?php endif; ?>
</main>
</body>
</html>
