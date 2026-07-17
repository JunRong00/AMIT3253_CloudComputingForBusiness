<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$equipment = $conn->query('SELECT * FROM equipment ORDER BY category, equipment_name')->fetch_all(MYSQLI_ASSOC);

$totalItems = count($equipment);
$totalUnits = array_sum(array_column($equipment, 'total_units'));

$pageTitle = 'All Equipment';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Equipment</h1>
<p>Laptops, projectors, and academic tools available to loan.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalItems ?></h3><p>Equipment types</p></div>
<div class="card"><h3><?= (int)$totalUnits ?></h3><p>Total units</p></div>
</div>
</section>

<?php foreach ($equipment as $e): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['equipment_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($e['equipment_name']) ?></h3>
<p>&#128193; <?= htmlspecialchars($e['category']) ?></p>
<?php if ($e['description']): ?><p><?= htmlspecialchars($e['description']) ?></p><?php endif; ?>
<p>Units available: <?= (int)$e['total_units'] ?></p>
<?php if (current_user_id()): ?>
<a class="btn" href="loan_create.php?equipment_id=<?= (int)$e['id'] ?>">Loan Now</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Loan</a>
<?php endif; ?>
</div>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
