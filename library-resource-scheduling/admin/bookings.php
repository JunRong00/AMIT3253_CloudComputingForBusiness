<?php
require '../config.php';
require '../auth.php';
require_admin();

$bookings = $conn->query('
    SELECT b.id, r.room_name, b.booking_date, b.time_slot, b.purpose, u.name AS user_name, u.email AS user_email
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.user_id
    ORDER BY b.booking_date DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Bookings';
require 'partials/header.php';
?>
<h1>All Bookings</h1>
<?php if (empty($bookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>No bookings yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Room</th><th>Date</th><th>Time Slot</th><th>Purpose</th><th>Booked By</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['room_name']) ?></td>
<td><?= htmlspecialchars($b['booking_date']) ?></td>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['purpose']) ?></td>
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
