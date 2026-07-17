<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare('SELECT * FROM vendors WHERE vendor_name LIKE ? ORDER BY vendor_name');
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param('s', $likeSearch);
    $stmt->execute();
    $vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);
}

$myBookings = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('
        SELECT b.id, v.vendor_name, b.booking_date, b.time_slot, b.purpose, b.quantity, b.estimated_total, v.unit_label
        FROM bookings b
        JOIN vendors v ON v.id = b.vendor_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'On-Campus Vendor Services';
require 'partials/header.php';
?>
<section class="hero">
<h1>On-Campus Vendor Services</h1>
<p>Book a time slot with printing, laundry, tailoring and repair vendors on campus.</p>
</section>

<section>
<h2>Available Vendors</h2>
<form method="get" class="filter-bar" id="vendor-filter-form">
<label>Search <input type="text" name="q" id="vendor-search" placeholder="Vendor name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('vendor-search');
    var form = document.getElementById('vendor-filter-form');
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

<?php if (empty($vendors)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No vendors match your search.</p>
<a class="btn btn-small btn-secondary" href="index.php">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($vendors as $v): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($v)) ?>" alt="<?= htmlspecialchars($v['vendor_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($v['vendor_name']) ?></h3>
<p><?= htmlspecialchars($v['category']) ?> &middot; <?= htmlspecialchars($v['location']) ?></p>
<?php if (!empty($v['description'])): ?><p><?= htmlspecialchars($v['description']) ?></p><?php endif; ?>
<p class="price-tag">RM<?= number_format($v['price_per_unit'], 2) ?> / <?= htmlspecialchars($v['unit_label']) ?></p>
<?php if (current_user_id()): ?>
<a class="btn" href="create.php?vendor_id=<?= (int)$v['id'] ?>">Book Slot</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Bookings</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your bookings.</p>
<?php elseif (empty($myBookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>You have no bookings yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Vendor</th><th>Date</th><th>Time Slot</th><th>Purpose</th><th>Qty</th><th>Est. Total (RM)</th><th>Actions</th></tr>
<?php foreach ($myBookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['vendor_name']) ?></td>
<td><?= htmlspecialchars($b['booking_date']) ?></td>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['purpose']) ?></td>
<td><?= (int)$b['quantity'] ?> <?= htmlspecialchars($b['unit_label']) ?>(s)</td>
<td><?= number_format($b['estimated_total'], 2) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
