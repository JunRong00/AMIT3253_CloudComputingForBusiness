<?php
require 'config.php';
require 'auth.php';
require_login();

$id  = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('
    SELECT b.id, b.booking_date, f.name AS facility_name, f.location, co.name AS court_name, t.label AS time_slot
    FROM bookings b
    JOIN courts co ON co.id = b.court_id
    JOIN facilities f ON f.id = co.facility_id
    JOIN time_slots t ON t.id = b.time_slot_id
    WHERE b.id = ? AND b.user_id = ?
');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die('Booking not found.');
}

$reference = 'BK-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);

$pageTitle = 'Booking Confirmed';
require 'partials/header.php';
?>
<div class="form-card confirmation-card">
<div class="confirmation-icon">&#10003;</div>
<h1>Booking Confirmed</h1>
<p class="stat-label">Reference <strong><?= htmlspecialchars($reference) ?></strong></p>

<table class="confirmation-table">
<tr><th>Facility</th><td><?= htmlspecialchars($booking['facility_name']) ?></td></tr>
<tr><th>Court</th><td><?= htmlspecialchars($booking['court_name']) ?></td></tr>
<tr><th>Location</th><td><?= htmlspecialchars($booking['location']) ?></td></tr>
<tr><th>Date</th><td><?= htmlspecialchars($booking['booking_date']) ?></td></tr>
<tr><th>Time Slot</th><td><?= htmlspecialchars($booking['time_slot']) ?></td></tr>
</table>

<div class="card-actions confirmation-actions">
<a class="btn" href="index.php">View My Bookings</a>
<a class="btn btn-secondary" href="index.php#facilities">Book Another</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
