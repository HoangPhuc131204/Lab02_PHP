<?php
require __DIR__ . '/lib/bootstrap.php';

$query = trim((string)($_GET['q'] ?? ''));
$category = (string)($_GET['category'] ?? 'all');
$allowedCategories = ['all','Web','AI','Design'];
if (!in_array($category, $allowedCategories, true)) $category = 'all';

$results = array_filter($workshops, static function(array $w) use ($query, $category): bool {
    $matchText = $query === '' || stripos($w['title'], $query) !== false;
    $matchCategory = $category === 'all' || $w['category'] === $category;
    return $matchText && $matchCategory;
});
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Workshop Portal</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
<header>
<h1>Workshop Booking Portal</h1>
<nav>
<a href="index.php">Catalog</a>
<a href="dashboard.php">My bookings</a>
</nav>
</header>
<p class="muted">GET search/filter • POST booking • server-side validation • session state</p>
<form action="index.php" method="get" class="panel form-row">
<label>Search <input name="q" value="<?= e($query) ?>" placeholder="PHP, AI, UX...">
</label>
<label>Category <select name="category">
<?php foreach ($allowedCategories as $c): ?>
<option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>>
<?= e($c) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<button>Search (GET)</button>
</form>
<p>Query parameters: <code>q=<?= e($query) ?>&amp;category=<?= e($category) ?></code>
</p>
<section class="grid">
<?php foreach ($results as $w): ?>
<article class="panel">
<span class="tag">
<?= e($w['category']) ?>
</span>
<h2>
<?= e($w['title']) ?>
</h2>
<p>
<?= number_format($w['fee']) ?> VND • <?= e($w['seats']) ?> seats/booking limit</p>
<a class="btn" href="booking.php?workshop_id=<?= urlencode($w['id']) ?>">Register</a>
</article>
<?php endforeach; ?>
</section>
<?php if (!$results): ?>
<p class="panel">No workshops matched your search.</p>
<?php endif; ?>
<form method="post" action="preference.php" class="panel form-row">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<label>Appearance <select name="theme">
<option value="light" <?= $theme==='light'?'selected':'' ?>>Light</option>
<option value="dark" <?= $theme==='dark'?'selected':'' ?>>Dark</option>
</select>
</label>
<button>Save preference (POST)</button>
</form>
</main>
</body>
</html>
