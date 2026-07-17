<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$routes = $conn->query('SELECT * FROM routes ORDER BY departure_time');

$pageTitle = 'Manage Routes';
require 'partials/header.php';
?>
<h1>Routes</h1>
<p><a class="btn btn-small" href="route_create.php">+ Add Route</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Route</th><th>Origin</th><th>Destination</th><th>Departs</th><th>Price (RM)</th><th>Seats</th><th>Actions</th></tr>
<?php while ($r = $routes->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($r)) ?>" alt="<?= htmlspecialchars($r['route_name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($r['route_name']) ?></td>
<td><?= htmlspecialchars($r['origin']) ?></td>
<td><?= htmlspecialchars($r['destination']) ?></td>
<td><?= htmlspecialchars($r['departure_time']) ?></td>
<td><?= number_format($r['price'], 2) ?></td>
<td><?= (int)$r['total_seats'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="route_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
<form action="route_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this route? Any existing tickets for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
