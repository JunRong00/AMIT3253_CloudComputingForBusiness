<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$rooms = $conn->query('SELECT * FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);

$totalRooms = count($rooms);
$totalCapacity = array_sum(array_column($rooms, 'capacity'));

$pageTitle = 'All Rooms';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Rooms</h1>
<p>Every discussion room and study pod available for booking.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalRooms ?></h3><p>Rooms available</p></div>
<div class="card"><h3><?= (int)$totalCapacity ?></h3><p>Combined capacity</p></div>
</div>
</section>

<?php foreach ($rooms as $r): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($r)) ?>" alt="<?= htmlspecialchars($r['room_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($r['room_name']) ?></h3>
<p>&#128205; <?= htmlspecialchars($r['location']) ?></p>
<p>Capacity: <?= (int)$r['capacity'] ?> people</p>
<?php if (current_user_id()): ?>
<a class="btn" href="create.php?room_id=<?= (int)$r['id'] ?>">Book Now</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
