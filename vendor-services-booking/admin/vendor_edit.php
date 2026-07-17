<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM vendors WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vendor) {
    die('Vendor not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_name    = trim($_POST['vendor_name']);
    $category       = trim($_POST['category']);
    $location       = trim($_POST['location']);
    $description    = trim($_POST['description']);
    $price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
    $unit_label     = trim($_POST['unit_label'] ?? '') ?: 'item';
    $capacity       = (int)($_POST['capacity'] ?? 1);

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'vendor');
    $image_url = $newImageUrl ?? $vendor['image_url'];

    if ($vendor_name === '' || $category === '' || $location === '') {
        $error = 'Vendor name, category and location are required.';
        $vendor = array_merge($vendor, compact('vendor_name', 'category', 'location', 'description', 'image_url', 'price_per_unit', 'unit_label', 'capacity'));
    } elseif ($price_per_unit < 0) {
        $error = 'Price per unit cannot be negative.';
        $vendor = array_merge($vendor, compact('vendor_name', 'category', 'location', 'description', 'image_url', 'price_per_unit', 'unit_label', 'capacity'));
    } elseif ($capacity < 1) {
        $error = 'Capacity per slot must be at least 1.';
        $vendor = array_merge($vendor, compact('vendor_name', 'category', 'location', 'description', 'image_url', 'price_per_unit', 'unit_label', 'capacity'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($vendor['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE vendors SET vendor_name=?, category=?, location=?, description=?, image_url=?, price_per_unit=?, unit_label=?, capacity=? WHERE id=?');
        $stmt->bind_param('sssssdsii', $vendor_name, $category, $location, $description, $image_url, $price_per_unit, $unit_label, $capacity, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: vendors.php');
        exit;
    }
}

$pageTitle = 'Edit Vendor';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Vendor</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$vendor['id'] ?>">
<label>Vendor Name <input type="text" name="vendor_name" value="<?= htmlspecialchars($vendor['vendor_name']) ?>" required></label>
<label>Category <input type="text" name="category" value="<?= htmlspecialchars($vendor['category']) ?>" required></label>
<label>Location <input type="text" name="location" value="<?= htmlspecialchars($vendor['location']) ?>" required></label>
<label>Description <textarea name="description" rows="3"><?= htmlspecialchars($vendor['description'] ?? '') ?></textarea></label>
<div class="card-row">
<label>Price per Unit (RM) <input type="number" name="price_per_unit" step="0.01" min="0" value="<?= htmlspecialchars($vendor['price_per_unit']) ?>"></label>
<label>Unit Label <input type="text" name="unit_label" placeholder="e.g. page, kg, item" value="<?= htmlspecialchars($vendor['unit_label']) ?>"></label>
</div>
<label>Capacity per Slot <input type="number" name="capacity" min="1" value="<?= (int)$vendor['capacity'] ?>"></label>
<p class="form-hint">How many students this vendor can serve in the same time slot.</p>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($vendor)) ?>" alt="<?= htmlspecialchars($vendor['vendor_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Vendor</button>
</form>
<p><a class="btn btn-secondary btn-small" href="vendors.php">Back to vendors</a></p>
</div>
<?php require 'partials/footer.php'; ?>
