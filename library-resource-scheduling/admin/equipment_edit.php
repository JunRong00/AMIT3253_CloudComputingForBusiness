<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM equipment WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$equipment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$equipment) {
    die('Equipment not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_name = trim($_POST['equipment_name']);
    $category       = trim($_POST['category']);
    $description    = trim($_POST['description'] ?? '');
    $total_units    = (int)$_POST['total_units'];

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'equipment');
    $image_url = $newImageUrl ?? $equipment['image_url'];

    if ($equipment_name === '' || $category === '' || $total_units < 1) {
        $error = 'Equipment name and category are required, and total units must be at least 1.';
        $equipment = array_merge($equipment, compact('equipment_name', 'category', 'description', 'total_units', 'image_url'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($equipment['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE equipment SET equipment_name=?, category=?, description=?, total_units=?, image_url=? WHERE id=?');
        $stmt->bind_param('sssisi', $equipment_name, $category, $description, $total_units, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: equipment.php');
        exit;
    }
}

$pageTitle = 'Edit Equipment';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Equipment</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$equipment['id'] ?>">
<label>Equipment Name <input type="text" name="equipment_name" value="<?= htmlspecialchars($equipment['equipment_name']) ?>" required></label>
<label>Category <input type="text" name="category" value="<?= htmlspecialchars($equipment['category']) ?>" required></label>
<label>Description <input type="text" name="description" value="<?= htmlspecialchars($equipment['description'] ?? '') ?>"></label>
<label>Total Units <input type="number" name="total_units" min="1" value="<?= (int)$equipment['total_units'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($equipment)) ?>" alt="<?= htmlspecialchars($equipment['equipment_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Equipment</button>
</form>
<p><a class="btn btn-secondary btn-small" href="equipment.php">Back to equipment</a></p>
</div>
<?php require 'partials/footer.php'; ?>
