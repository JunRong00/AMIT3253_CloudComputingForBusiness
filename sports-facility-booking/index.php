<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare('SELECT * FROM facilities WHERE name LIKE ? ORDER BY name');
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param('s', $likeSearch);
    $stmt->execute();
    $facilities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $facilities = $conn->query('SELECT * FROM facilities ORDER BY name')->fetch_all(MYSQLI_ASSOC);
}

$notifications = [];
$myBookings = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('SELECT id, message, created_at FROM notifications WHERE user_id = ? AND read_at IS NULL ORDER BY created_at DESC');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('
        SELECT b.id, f.name AS facility_name, co.name AS court_name, b.booking_date, t.label AS time_slot
        FROM bookings b
        JOIN courts co ON co.id = b.court_id
        JOIN facilities f ON f.id = co.facility_id
        JOIN time_slots t ON t.id = b.time_slot_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date, t.sort_order
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'Sports Facility Booking';
require 'partials/header.php';
?>
<?php foreach ($notifications as $n): ?>
<div class="alert alert-warning">
<span>&#128276; <?= htmlspecialchars($n['message']) ?></span>
<form action="notification_dismiss.php" method="post">
<input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
<button type="submit" class="btn btn-small btn-secondary">Dismiss</button>
</form>
</div>
<?php endforeach; ?>

<div class="page-header">
<h1>Campus Sports Facility Booking</h1>
<p>Reserve badminton courts, futsal courts and more in a few clicks.</p>
</div>

<section>
<h2 id="facilities">Available Facilities</h2>
<form method="get" class="filter-bar" id="facility-filter-form">
<label>Search <input type="text" name="q" id="facility-search" placeholder="Facility name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a class="btn btn-secondary" href="index.php#facilities">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('facility-search');
    var form = document.getElementById('facility-filter-form');
    if (!input || !form) return;
    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.submit();
        }, 500);
    });
})();
</script>

<?php if (empty($facilities)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No facilities match your search.</p>
<a class="btn btn-small btn-secondary" href="index.php#facilities">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($facilities as $f): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(facility_image_url($f)) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($f['name']) ?></h3>
<p><?= htmlspecialchars($f['location']) ?></p>
<p>Capacity: <?= (int)$f['capacity'] ?></p>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="schedule.php?facility_id=<?= (int)$f['id'] ?>">View Schedule</a>
<?php if (current_user_id()): ?>
<a class="btn btn-small" href="create.php?facility_id=<?= (int)$f['id'] ?>">Book Now</a>
<?php else: ?>
<a class="btn btn-small" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Bookings</h2>
<?php if (!current_user_id()): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128100;</div>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your bookings.</p>
</div>
<?php elseif (empty($myBookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>You have no bookings yet. Book a facility above to get started.</p>
</div>
<?php else: ?>
<table>
<tr><th>Facility</th><th>Court</th><th>Date</th><th>Time Slot</th><th>Actions</th></tr>
<?php foreach ($myBookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['facility_name']) ?></td>
<td><?= htmlspecialchars($b['court_name']) ?></td>
<td><?= htmlspecialchars($b['booking_date']) ?></td>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
