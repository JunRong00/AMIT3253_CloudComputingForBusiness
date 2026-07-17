<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_name = trim($_POST['equipment_name']);
    $category       = trim($_POST['category']);
    $description    = trim($_POST['description'] ?? '');
    $total_units    = (int)$_POST['total_units'];

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'equipment');

    if ($equipment_name === '' || $category === '' || $total_units < 1) {
        $error = 'Equipment name and category are required, and total units must be at least 1.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO equipment (equipment_name, category, description, total_units, image_url) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssis', $equipment_name, $category, $description, $total_units, $image_url);
        $stmt->execute();
        $stmt->close();
        header('Location: equipment.php');
        exit;
    }
}

$pageTitle = 'Add Equipment';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Equipment</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Equipment Name <input type="text" name="equipment_name" required></label>
<label>Category <input type="text" name="category" placeholder="e.g. Computing, AV Equipment" required></label>
<label>Description <input type="text" name="description"></label>
<label>Total Units <input type="number" name="total_units" min="1" required></label>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Equipment</button>
</form>
<p><a class="btn btn-secondary btn-small" href="equipment.php">Back to equipment</a></p>
</div>
<?php require 'partials/footer.php'; ?>
