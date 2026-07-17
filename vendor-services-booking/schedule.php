<?php
require 'config.php';
require 'auth.php';

$date = $_GET['date'] ?? date('Y-m-d');

$vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare('
    SELECT vendor_id, time_slot, COUNT(*) AS booked
    FROM bookings
    WHERE booking_date = ?
    GROUP BY vendor_id, time_slot
    ORDER BY time_slot
');
$stmt->bind_param('s', $date);
$stmt->execute();
$bookingCounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$countsByVendor = [];
foreach ($bookingCounts as $b) {
    $countsByVendor[$b['vendor_id']][$b['time_slot']] = $b['booked'];
}

$pageTitle = 'Vendor Schedule';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Vendor Schedule</h1>
<p>Pick a date to see which vendors already have booked slots.</p>
</div>

<form method="get" class="filter-bar">
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
<button type="submit">View</button>
</form>

<?php foreach ($vendors as $v): ?>
<section>
<h2><?= htmlspecialchars($v['vendor_name']) ?> <span class="badge badge-neutral"><?= htmlspecialchars($v['category']) ?></span></h2>
<p class="form-hint">Serves up to <?= (int)$v['capacity'] ?> booking(s) per time slot.</p>
<?php if (empty($countsByVendor[$v['id']])): ?>
<div class="empty-state">
<div class="empty-state-icon">&#9989;</div>
<p>No bookings yet on <?= htmlspecialchars($date) ?>.</p>
</div>
<?php else: ?>
<table>
<tr><th>Time Slot</th><th>Status</th></tr>
<?php foreach ($countsByVendor[$v['id']] as $slot => $booked): ?>
<tr>
<td><?= htmlspecialchars($slot) ?></td>
<td><?php if ($booked >= $v['capacity']): ?><span class="badge badge-neutral">Full (<?= (int)$booked ?>/<?= (int)$v['capacity'] ?>)</span><?php else: ?><span class="badge badge-accent"><?= (int)$booked ?>/<?= (int)$v['capacity'] ?> booked</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php if (current_user_id()): ?>
<a class="btn btn-small" href="create.php?vendor_id=<?= (int)$v['id'] ?>">Book This Vendor</a>
<?php endif; ?>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
