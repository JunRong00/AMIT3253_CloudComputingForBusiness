<?php
require '../config.php';
require '../auth.php';
require_admin();

$date = $_GET['date'] ?? date('Y-m-d');

$vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare('
    SELECT b.vendor_id, b.time_slot, b.purpose, u.name, u.email
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.booking_date = ?
    ORDER BY b.time_slot
');
$stmt->bind_param('s', $date);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$bookingsByVendor = [];
foreach ($bookings as $b) {
    $bookingsByVendor[$b['vendor_id']][] = $b;
}

$pageTitle = 'Vendor Schedule';
require 'partials/header.php';
?>
<h1>Vendor Schedule</h1>
<p>Pick a date to see which vendors are booked, and by whom.</p>

<form method="get" class="filter-bar">
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
<button type="submit">View</button>
</form>

<?php foreach ($vendors as $v): ?>
<section>
<h2><?= htmlspecialchars($v['vendor_name']) ?> <span class="badge badge-neutral"><?= htmlspecialchars($v['category']) ?></span></h2>
<p class="form-hint">Capacity: <?= (int)$v['capacity'] ?> booking(s) per time slot.</p>
<?php if (empty($bookingsByVendor[$v['id']])): ?>
<div class="empty-state">
<div class="empty-state-icon">&#9989;</div>
<p>No bookings yet on <?= htmlspecialchars($date) ?>.</p>
</div>
<?php else: ?>
<table>
<tr><th>Time Slot</th><th>Purpose</th><th>Booked By</th><th>Email</th></tr>
<?php foreach ($bookingsByVendor[$v['id']] as $b): ?>
<tr>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['purpose']) ?></td>
<td><?= htmlspecialchars($b['name']) ?></td>
<td><?= htmlspecialchars($b['email']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
