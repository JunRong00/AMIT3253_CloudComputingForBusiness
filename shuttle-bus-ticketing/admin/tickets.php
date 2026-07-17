<?php
require '../config.php';
require '../auth.php';
require_admin();

$tickets = $conn->query('
    SELECT t.id, r.route_name, t.travel_date, t.seat_quantity, t.total_price, u.name AS user_name, u.email AS user_email
    FROM tickets t
    JOIN routes r ON r.id = t.route_id
    JOIN users u ON u.id = t.user_id
    ORDER BY t.travel_date DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Tickets';
require 'partials/header.php';
?>
<h1>All Tickets</h1>
<?php if (empty($tickets)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128196;</div>
<p>No tickets booked yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Route</th><th>Travel Date</th><th>Seats</th><th>Total (RM)</th><th>Booked By</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($tickets as $t): ?>
<tr>
<td><?= htmlspecialchars($t['route_name']) ?></td>
<td><?= htmlspecialchars($t['travel_date']) ?></td>
<td><?= (int)$t['seat_quantity'] ?></td>
<td><?= number_format($t['total_price'], 2) ?></td>
<td><?= htmlspecialchars($t['user_name']) ?></td>
<td><?= htmlspecialchars($t['user_email']) ?></td>
<td>
<form action="ticket_cancel.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this ticket?');">
<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
