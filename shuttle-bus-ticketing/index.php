<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare('SELECT * FROM routes WHERE route_name LIKE ? ORDER BY departure_time');
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param('s', $likeSearch);
    $stmt->execute();
    $routes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $routes = $conn->query('SELECT * FROM routes ORDER BY departure_time')->fetch_all(MYSQLI_ASSOC);
}

$myTickets = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('
        SELECT t.id, r.route_name, r.origin, r.destination, r.departure_time, t.travel_date, t.seat_quantity, t.total_price
        FROM tickets t
        JOIN routes r ON r.id = t.route_id
        WHERE t.user_id = ?
        ORDER BY t.travel_date DESC
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myTickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'Campus Shuttle Bus Ticketing';
require 'partials/header.php';
?>
<section class="hero">
<h1>Campus Shuttle Bus Ticketing</h1>
<p>Book your seat on a campus shuttle route ahead of time.</p>
</section>

<section>
<h2>Available Routes</h2>
<form method="get" class="filter-bar" id="route-filter-form">
<label>Search <input type="text" name="q" id="route-search" placeholder="Route name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('route-search');
    var form = document.getElementById('route-filter-form');
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

<?php if (empty($routes)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No routes match your search.</p>
<a class="btn btn-small btn-secondary" href="index.php">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($routes as $r): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($r)) ?>" alt="<?= htmlspecialchars($r['route_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($r['route_name']) ?></h3>
<p><?= htmlspecialchars($r['origin']) ?> &rarr; <?= htmlspecialchars($r['destination']) ?></p>
<p>Departs <?= htmlspecialchars($r['departure_time']) ?> &middot; RM<?= number_format($r['price'], 2) ?> &middot; <?= (int)$r['total_seats'] ?> seats/bus</p>
<?php if (current_user_id()): ?>
<a class="btn" href="create.php?route_id=<?= (int)$r['id'] ?>">Book Ticket</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Tickets</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your tickets.</p>
<?php elseif (empty($myTickets)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128196;</div>
<p>You haven't booked any tickets yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Route</th><th>Travel Date</th><th>Departs</th><th>Seats</th><th>Total (RM)</th><th>Actions</th></tr>
<?php foreach ($myTickets as $t): ?>
<tr>
<td><?= htmlspecialchars($t['route_name']) ?></td>
<td><?= htmlspecialchars($t['travel_date']) ?></td>
<td><?= htmlspecialchars($t['departure_time']) ?></td>
<td><?= (int)$t['seat_quantity'] ?></td>
<td><?= number_format($t['total_price'], 2) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$t['id'] ?>">Edit</a>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this ticket?');">
<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
