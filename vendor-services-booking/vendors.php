<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);

$totalVendors = count($vendors);
$totalCategories = count(array_unique(array_column($vendors, 'category')));

$pageTitle = 'All Vendors';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Vendors</h1>
<p>Every on-campus vendor offering bookable service slots.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalVendors ?></h3><p>Vendors on campus</p></div>
<div class="card"><h3><?= (int)$totalCategories ?></h3><p>Service categories</p></div>
</div>
</section>

<?php foreach ($vendors as $v): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($v)) ?>" alt="<?= htmlspecialchars($v['vendor_name']) ?>" loading="lazy">
<span class="badge badge-accent"><?= htmlspecialchars($v['category']) ?></span>
<h3><?= htmlspecialchars($v['vendor_name']) ?></h3>
<p>&#128205; <?= htmlspecialchars($v['location']) ?></p>
<?php if (!empty($v['description'])): ?><p><?= htmlspecialchars($v['description']) ?></p><?php endif; ?>
<p class="price-tag">RM<?= number_format($v['price_per_unit'], 2) ?> / <?= htmlspecialchars($v['unit_label']) ?></p>
<?php if (current_user_id()): ?>
<a class="btn" href="create.php?vendor_id=<?= (int)$v['id'] ?>">Book Slot</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
