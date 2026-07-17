<?php
require '../config.php';
require '../auth.php';
require_admin();

$bookings = $conn->query('
    SELECT b.id, f.name AS facility_name, co.name AS court_name, t.label AS time_slot, b.booking_date, u.name AS user_name, u.email AS user_email
    FROM bookings b
    JOIN courts co ON co.id = b.court_id
    JOIN facilities f ON f.id = co.facility_id
    JOIN time_slots t ON t.id = b.time_slot_id
    JOIN users u ON u.id = b.user_id
    ORDER BY b.booking_date DESC, t.sort_order
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Bookings';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Bookings</h1>
<p>Every booking across all users, most recent first.</p>
</div>
<?php if (empty($bookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>No bookings yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Date</th><th>Time Slot</th><th>Facility</th><th>Court</th><th>Booked By</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['booking_date']) ?></td>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['facility_name']) ?></td>
<td><?= htmlspecialchars($b['court_name']) ?></td>
<td><?= htmlspecialchars($b['user_name']) ?></td>
<td><?= htmlspecialchars($b['user_email']) ?></td>
<td>
<form action="booking_cancel.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
