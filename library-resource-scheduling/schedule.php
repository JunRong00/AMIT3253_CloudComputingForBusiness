<?php
require 'config.php';
require 'auth.php';

$date = $_GET['date'] ?? date('Y-m-d');

$rooms = $conn->query('SELECT * FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare('
    SELECT room_id, time_slot, purpose
    FROM bookings
    WHERE booking_date = ?
    ORDER BY time_slot
');
$stmt->bind_param('s', $date);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$bookingsByRoom = [];
foreach ($bookings as $b) {
    $bookingsByRoom[$b['room_id']][] = $b;
}

$pageTitle = 'Room Schedule';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Room Schedule</h1>
<p>Pick a date to see which rooms already have bookings.</p>
</div>

<form method="get" class="filter-bar">
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
<button type="submit">View</button>
</form>

<?php foreach ($rooms as $r): ?>
<section>
<h2><?= htmlspecialchars($r['room_name']) ?> <span class="badge badge-neutral"><?= htmlspecialchars($r['location']) ?></span></h2>
<?php if (empty($bookingsByRoom[$r['id']])): ?>
<div class="empty-state">
<div class="empty-state-icon">&#9989;</div>
<p>No bookings yet on <?= htmlspecialchars($date) ?>.</p>
</div>
<?php else: ?>
<table>
<tr><th>Time Slot</th><th>Status</th></tr>
<?php foreach ($bookingsByRoom[$r['id']] as $b): ?>
<tr>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><span class="badge badge-neutral">Booked</span></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php if (current_user_id()): ?>
<a class="btn btn-small" href="create.php?room_id=<?= (int)$r['id'] ?>">Book This Room</a>
<?php endif; ?>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
