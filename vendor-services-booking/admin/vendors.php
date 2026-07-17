<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name');

$pageTitle = 'Manage Vendors';
require 'partials/header.php';
?>
<h1>Vendors</h1>
<p><a class="btn btn-small" href="vendor_create.php">+ Add Vendor</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Vendor Name</th><th>Category</th><th>Location</th><th>Price</th><th>Capacity/Slot</th><th>Actions</th></tr>
<?php while ($v = $vendors->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($v)) ?>" alt="<?= htmlspecialchars($v['vendor_name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($v['vendor_name']) ?></td>
<td><?= htmlspecialchars($v['category']) ?></td>
<td><?= htmlspecialchars($v['location']) ?></td>
<td>RM<?= number_format($v['price_per_unit'], 2) ?> / <?= htmlspecialchars($v['unit_label']) ?></td>
<td><?= (int)$v['capacity'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="vendor_edit.php?id=<?= (int)$v['id'] ?>">Edit</a>
<form action="vendor_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this vendor? Any existing bookings for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
