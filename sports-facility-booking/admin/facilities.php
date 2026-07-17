<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$facilities = $conn->query('SELECT * FROM facilities ORDER BY name');

$pageTitle = 'Manage Facilities';
require 'partials/header.php';
?>
<div class="page-header">
<div class="page-header-top">
<div>
<h1>Facilities</h1>
<p>Manage bookable sports facilities.</p>
</div>
<a class="btn btn-small" href="facility_create.php">+ Add Facility</a>
</div>
</div>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Name</th><th>Location</th><th>Capacity</th><th>Actions</th></tr>
<?php while ($f = $facilities->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(facility_image_url($f)) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($f['name']) ?></td>
<td><?= htmlspecialchars($f['location']) ?></td>
<td><?= (int)$f['capacity'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="facility_edit.php?id=<?= (int)$f['id'] ?>">Edit</a>
<form action="facility_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this facility?');">
<input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
