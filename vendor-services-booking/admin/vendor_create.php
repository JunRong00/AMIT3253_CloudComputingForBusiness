<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_name    = trim($_POST['vendor_name']);
    $category       = trim($_POST['category']);
    $location       = trim($_POST['location']);
    $description    = trim($_POST['description']);
    $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
    $unit_label     = trim($_POST['unit_label'] ?? '') ?: 'item';
    $capacity       = (int)($_POST['capacity'] ?? 1);

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'vendor');

    if ($vendor_name === '' || $category === '' || $location === '') {
        $error = 'Vendor name, category and location are required.';
    } elseif ($price_per_unit < 0) {
        $error = 'Price per unit cannot be negative.';
    } elseif ($capacity < 1) {
        $error = 'Capacity per slot must be at least 1.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO vendors (vendor_name, category, location, description, image_url, price_per_unit, unit_label, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssdsi', $vendor_name, $category, $location, $description, $image_url, $price_per_unit, $unit_label, $capacity);
        $stmt->execute();
        $stmt->close();
        header('Location: vendors.php');
        exit;
    }
}

$pageTitle = 'Add Vendor';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Vendor</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Vendor Name <input type="text" name="vendor_name" required></label>
<label>Category <input type="text" name="category" placeholder="e.g. Printing & Stationery" required></label>
<label>Location <input type="text" name="location" required></label>
<label>Description <textarea name="description" rows="3"></textarea></label>
<div class="card-row">
<label>Price per Unit (RM) <input type="number" name="price_per_unit" step="0.01" min="0" value="0.00"></label>
<label>Unit Label <input type="text" name="unit_label" placeholder="e.g. page, kg, item" value="item"></label>
</div>
<p class="form-hint">Shown to students as "RM x.xx / unit" so they know roughly what to expect - the vendor confirms the exact price in person, there's no online payment.</p>
<label>Capacity per Slot <input type="number" name="capacity" min="1" value="1"></label>
<p class="form-hint">How many students this vendor can serve in the same time slot. Once that many bookings exist for a slot, students booking that vendor see it as full and must pick another time.</p>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Vendor</button>
</form>
<p><a class="btn btn-secondary btn-small" href="vendors.php">Back to vendors</a></p>
</div>
<?php require 'partials/footer.php'; ?>
