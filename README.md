# PHP Lab 02 – Workshop Booking Portal (Form Input & Validation)

PHP-S07 Instruction app (Stages 0–9) + two new features written for Lab 02:

| Feature | File | Method |
|---|---|---|
| View Booking Detail | `booking_detail.php` | GET `?ref=BK-XXXXXX` (read-only) |
| Edit Booking | `booking_edit.php` | GET prefill + POST update (CSRF, validation, PRG 303) |

No database, no framework: bookings live in `$_SESSION['bookings']`, the theme preference in the `theme` cookie.

## Run

```bash
php -S localhost:8000        # from the project root
# open http://localhost:8000/
```
Requires PHP 8.1+ (uses `never` return type). With XAMPP copy the folder to `htdocs/php-s07-workshop`.

## Structure
```
index.php  booking.php  dashboard.php  preference.php  logout.php  request_demo.php
booking_detail.php   <- Lab 02 (new)
booking_edit.php     <- Lab 02 (new)
lib/bootstrap.php  lib/functions.php   (functions.php: + find_booking_index(), ref_from_query())
data/workshops.php
assets/style.css
tests/lab02_tests.py  (HTTP-level regression tests, `pip install requests`, server must be running)
```

## Changes compared with the Instruction source
* `booking.php` – the booking row now also stores `workshop_id` so Edit can re-read price/seats from the catalog.
* `dashboard.php` – new **Actions** column (View Details / Edit, `urlencode($ref)`).
* `lib/functions.php` – helpers `find_booking_index()` and `ref_from_query()`.
* `assets/style.css` – a few small classes (`.btn-sm`, `.detail`, …).

## Limits
Session-only demo: no login, no shared stock, no protection against two tabs overwriting each other.
