<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$equipment = $conn->query('SELECT * FROM equipment ORDER BY category, equipment_name');

$pageTitle = 'Manage Equipment';
require 'partials/header.php';
?>
<h1>Equipment</h1>
<p><a class="btn btn-small" href="equipment_create.php">+ Add Equipment</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Name</th><th>Category</th><th>Units</th><th>Actions</th></tr>
<?php while ($e = $equipment->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['equipment_name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($e['equipment_name']) ?></td>
<td><?= htmlspecialchars($e['category']) ?></td>
<td><?= (int)$e['total_units'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="equipment_edit.php?id=<?= (int)$e['id'] ?>">Edit</a>
<form action="equipment_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this equipment? Any existing loans for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
