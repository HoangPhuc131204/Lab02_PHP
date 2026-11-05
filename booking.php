<?php
require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if (!csrf_valid($_POST)) { http_response_code(403); exit('Invalid CSRF token'); }
    $result = validate_booking($_POST, $workshops);
    $_SESSION['form_errors'] = $result['errors'];
    $_SESSION['old_input'] = $result['data'];
    if (!$result['errors']) {
        $d = $result['data'];
        $w = $workshops[$d['workshop_id']];
        $_SESSION['bookings'][] = [
            'ref'=> 'BK-' . strtoupper(bin2hex(random_bytes(3))),
            'full_name'=>$d['full_name'], 'email'=>$d['email'],
            'workshop_id'=>$w['id'],            // Lab 02: keep the catalog key so Edit can re-read price/seats
            'workshop'=>$w['title'], 'quantity'=>$d['quantity'],
            'amount'=>$w['fee'] * $d['quantity']
        ];
        unset($_SESSION['old_input'], $_SESSION['form_errors']);
        $_SESSION['flash'] = 'Booking saved successfully.';
        redirect_to('dashboard.php');
    }
    redirect_to('booking.php?workshop_id=' . urlencode($result['data']['workshop_id']));
}

if ($method !== 'GET') { http_response_code(405); exit('Method not allowed'); }
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);
$id = (string)($old['workshop_id'] ?? $_GET['workshop_id'] ?? '');
$selected = $workshops[$id] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Book workshop</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<header>
<h1>Book a Workshop</h1>
<nav>
<a href="index.php">Catalog</a>
<a href="dashboard.php">My bookings</a>
</nav>
</header>
<form method="post" action="booking.php" class="panel form-stack" novalidate>
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<label>Workshop <select name="workshop_id">
<option value="">Choose...</option>
<?php foreach ($workshops as $w): ?>
<option value="<?= e($w['id']) ?>" <?= $id===$w['id']?'selected':'' ?>>
<?= e($w['title']) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<small class="error">
<?= e($errors['workshop_id'] ?? '') ?>
</small>
<label>Full name <input name="full_name" value="<?= e($old['full_name'] ?? '') ?>" placeholder="Nguyen Van A">
</label>
<small class="error">
<?= e($errors['full_name'] ?? '') ?>
</small>
<label>Email <input name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="user@example.com">
</label>
<small class="error">
<?= e($errors['email'] ?? '') ?>
</small>
<label>Tickets (1–4) <input name="quantity" type="number" min="1" max="4" value="<?= e($old['quantity'] ?? 1) ?>">
</label>
<small class="error">
<?= e($errors['quantity'] ?? '') ?>
</small>
<button>Confirm booking (POST)</button>
</form>
<p class="muted">Validation always runs on server even when browser validation is disabled.</p>
</main>
</body>
</html>
