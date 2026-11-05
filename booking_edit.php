<?php
/**
 * Lab 02 – Feature B: Edit Booking (GET prefill + POST update).
 * E01 load | E02 request gate + CSRF | E03 validation | E04 update one booking
 * E05 recalculate amount on server | E06 failure keeps input | E07 success PRG 303 | E08 unknown ref
 */
require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$ref   = ref_from_query();                              // ref is taken ONLY from the URL, never from the form
$index = find_booking_index($_SESSION['bookings'], $ref);

/* ---------- E08: unknown / missing ref => no write, no new booking ---------- */
if ($index === null) {
    // CSRF is still checked for POST so a forged request is a 403, not a "soft" 404.
    if ($method === 'POST' && !csrf_valid($_POST)) { http_response_code(403); exit('Invalid CSRF token'); }
    http_response_code(404);
    $notFound = true;
} else {
    $notFound = false;
    $booking  = $_SESSION['bookings'][$index];
    // Workshop and price are re-read from the server catalog (trusted), not from the client.
    $catalogItem = $workshops[$booking['workshop_id'] ?? ''] ?? null;

    $errors = [];
    $old = [
        'full_name' => $booking['full_name'],
        'email'     => $booking['email'],
        'quantity'  => $booking['quantity'],
    ];

    /* ---------- E02: only a POST with a valid CSRF token may change the session ---------- */
    if ($method === 'POST') {
        if (!csrf_valid($_POST)) { http_response_code(403); exit('Invalid CSRF token'); }

        if ($catalogItem === null) {
            $errors['workshop_id'] = 'This workshop no longer exists in the catalog.';
        }

        // E03: reuse validate_booking(). full_name/email/quantity come from the form;
        // workshop_id comes from the STORED booking – any workshop_id/price/total/ref in $_POST is ignored.
        $result = validate_booking([
            'full_name'   => $_POST['full_name'] ?? '',
            'email'       => $_POST['email'] ?? '',
            'quantity'    => $_POST['quantity'] ?? null,
            'workshop_id' => $booking['workshop_id'] ?? '',
        ], $workshops);
        $errors = $result['errors'] + $errors;
        $old = [                                           // E06: keep what the user typed
            'full_name' => $result['data']['full_name'],
            'email'     => $result['data']['email'],
            'quantity'  => (string)($_POST['quantity'] ?? ''),
        ];

        if (!$errors) {
            // E04: update ONLY the element found by ref; other bookings are untouched.
            $d = $result['data'];
            $_SESSION['bookings'][$index]['full_name'] = $d['full_name'];
            $_SESSION['bookings'][$index]['email']     = $d['email'];
            $_SESSION['bookings'][$index]['quantity']  = $d['quantity'];
            // E05: amount = trusted catalog fee x validated quantity.
            $_SESSION['bookings'][$index]['amount']    = $workshops[$d['workshop_id']]['fee'] * $d['quantity'];

            $_SESSION['flash'] = 'Booking ' . $booking['ref'] . ' updated successfully.';
            // E07: POST/Redirect/GET with HTTP 303 -> refresh cannot re-submit the form.
            redirect_to('dashboard.php');
        }
        http_response_code(422);                           // E06: session left unchanged
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit booking</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<header>
<h1>Edit Booking</h1>
<nav>
<a href="index.php">Catalog</a>
<a href="dashboard.php">My bookings</a>
</nav>
</header>

<?php if ($notFound): ?>
<div class="panel">
<p class="notice warn"><strong>Booking not found.</strong> Nothing was changed. The booking may not exist or your session may have been cleared.</p>
<a class="btn" href="dashboard.php">Back to My Bookings</a>
</div>

<?php else: ?>
<form method="post" action="booking_edit.php?ref=<?= urlencode($booking['ref']) ?>" class="panel form-stack" novalidate>
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

<label>Reference <input value="<?= e($booking['ref']) ?>" readonly></label>
<label>Workshop <input value="<?= e($booking['workshop']) ?>" readonly></label>
<small class="error"><?= e($errors['workshop_id'] ?? '') ?></small>

<label>Full name <input name="full_name" value="<?= e($old['full_name']) ?>" placeholder="Nguyen Van A"></label>
<small class="error"><?= e($errors['full_name'] ?? '') ?></small>

<label>Email <input name="email" value="<?= e($old['email']) ?>" placeholder="user@example.com"></label>
<small class="error"><?= e($errors['email'] ?? '') ?></small>

<label>Tickets (1–4) <input name="quantity" type="number" min="1" max="4" value="<?= e($old['quantity']) ?>"></label>
<small class="error"><?= e($errors['quantity'] ?? '') ?></small>

<p class="muted">Unit fee: <?= $catalogItem ? number_format($catalogItem['fee']) . ' VND' : 'n/a' ?> — the total is recalculated on the server when you save.</p>

<div class="actions-bar">
<button>Save Changes (POST)</button>
<a class="btn secondary" href="booking_detail.php?ref=<?= urlencode($booking['ref']) ?>">Cancel</a>
<a class="btn secondary" href="dashboard.php">Back to My Bookings</a>
</div>
</form>
<?php endif; ?>
</main>
</body>
</html>
