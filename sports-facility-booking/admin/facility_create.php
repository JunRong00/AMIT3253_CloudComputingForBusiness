<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name']);
    $location      = trim($_POST['location']);
    $capacity      = (int)$_POST['capacity'];
    $description   = trim($_POST['description']);
    $materials     = trim($_POST['materials']);
    $rules         = trim($_POST['rules']);
    $first_court   = trim($_POST['first_court']);

    [$image_url, $uploadError] = handle_facility_image_upload($_FILES['image'] ?? null, $uploadDir, 'facility');

    if ($name === '' || $location === '' || $capacity < 1 || $first_court === '') {
        $error = 'Name, location, capacity and the first court name are all required.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO facilities (name, location, capacity, description, materials, rules, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssissss', $name, $location, $capacity, $description, $materials, $rules, $image_url);
        $stmt->execute();
        $facility_id = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO courts (facility_id, name) VALUES (?, ?)');
        $stmt->bind_param('is', $facility_id, $first_court);
        $stmt->execute();
        $stmt->close();

        header('Location: facilities.php');
        exit;
    }
}

$pageTitle = 'Add Facility';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Facility</h1>
<p class="stat-label">A facility is a sport/category (e.g. "Badminton"). You'll add its first bookable court below, and can add more courts afterwards from the Courts page.</p>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Name <input type="text" name="name" placeholder="e.g. Badminton" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></label>
<label>Location <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required></label>
<label>Capacity (players per booked session) <input type="number" name="capacity" min="1" value="<?= htmlspecialchars($_POST['capacity'] ?? '1') ?>" required></label>
<label>First Court Name <input type="text" name="first_court" placeholder="e.g. Court 1" value="<?= htmlspecialchars($_POST['first_court'] ?? '') ?>" required></label>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<label>Description <textarea name="description" rows="3" placeholder="Shown on the public Facilities page"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></label>
<label>Construction &amp; Materials <textarea name="materials" rows="3" placeholder="Flooring, fixtures, build details..."><?= htmlspecialchars($_POST['materials'] ?? '') ?></textarea></label>
<label>House Rules (one per line) <textarea name="rules" rows="4" placeholder="One rule per line"><?= htmlspecialchars($_POST['rules'] ?? '') ?></textarea></label>
<button type="submit">Add Facility</button>
</form>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="facilities.php">Back to Facilities</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
