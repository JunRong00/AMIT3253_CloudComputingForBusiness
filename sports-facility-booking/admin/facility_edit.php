<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$stmt = $conn->prepare('SELECT * FROM facilities WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$facility) {
    die('Facility not found.');
}

$courts = $conn->prepare('SELECT id, name FROM courts WHERE facility_id = ? ORDER BY name');
$courts->bind_param('i', $id);
$courts->execute();
$courtList = $courts->get_result()->fetch_all(MYSQLI_ASSOC);
$courts->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $location    = trim($_POST['location']);
    $capacity    = (int)$_POST['capacity'];
    $description = trim($_POST['description']);
    $materials   = trim($_POST['materials']);
    $rules       = trim($_POST['rules']);

    [$newImageUrl, $uploadError]   = handle_facility_image_upload($_FILES['image'] ?? null, $uploadDir, 'facility');
    [$newLayoutUrl, $layoutError]  = handle_facility_image_upload($_FILES['layout'] ?? null, $uploadDir, 'layout');
    $image_url  = $newImageUrl ?? $facility['image_url'];
    $layout_url = $newLayoutUrl ?? $facility['layout_url'];

    if ($name === '' || $location === '' || $capacity < 1) {
        $error = 'Name and location are required and capacity must be at least 1.';
        $facility = ['id' => $id, 'name' => $name, 'location' => $location, 'capacity' => $capacity, 'description' => $description, 'materials' => $materials, 'rules' => $rules, 'image_url' => $image_url, 'layout_url' => $layout_url];
    } elseif ($uploadError) {
        $error = $uploadError;
    } elseif ($layoutError) {
        $error = $layoutError;
    } else {
        if ($newImageUrl) {
            delete_facility_image_file($facility['image_url'], $uploadDir);
        }
        if ($newLayoutUrl) {
            delete_facility_image_file($facility['layout_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE facilities SET name=?, location=?, capacity=?, description=?, materials=?, rules=?, image_url=?, layout_url=? WHERE id=?');
        $stmt->bind_param('ssisssssi', $name, $location, $capacity, $description, $materials, $rules, $image_url, $layout_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: facilities.php');
        exit;
    }
}

$pageTitle = 'Edit Facility';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Facility</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$facility['id'] ?>">
<label>Name <input type="text" name="name" value="<?= htmlspecialchars($facility['name']) ?>" required></label>
<label>Location <input type="text" name="location" value="<?= htmlspecialchars($facility['location']) ?>" required></label>
<label>Capacity (players per booked session) <input type="number" name="capacity" min="1" value="<?= (int)$facility['capacity'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(facility_image_url($facility)) ?>" alt="<?= htmlspecialchars($facility['name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<?php if (facility_layout_url($facility)): ?>
<label>Current Layout Diagram
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(facility_layout_url($facility)) ?>" alt="Layout">
</label>
<?php endif; ?>
<label>Replace Layout Diagram <input type="file" name="layout" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<label>Description <textarea name="description" rows="3" placeholder="Shown on the public Facilities page"><?= htmlspecialchars($facility['description'] ?? '') ?></textarea></label>
<label>Construction &amp; Materials <textarea name="materials" rows="3" placeholder="Flooring, fixtures, build details..."><?= htmlspecialchars($facility['materials'] ?? '') ?></textarea></label>
<label>House Rules (one per line) <textarea name="rules" rows="4" placeholder="One rule per line"><?= htmlspecialchars($facility['rules'] ?? '') ?></textarea></label>
<button type="submit">Update Facility</button>
</form>

<h2 style="margin-top:24px;">Courts</h2>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Court</th><th>Actions</th></tr>
<?php foreach ($courtList as $c): ?>
<tr>
<td><?= htmlspecialchars($c['name']) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="court_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
<form action="court_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this court? Any bookings for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<div class="card-actions">
<a class="btn btn-small" href="court_create.php?facility_id=<?= (int)$id ?>">+ Add Court</a>
</div>

<div class="card-actions" style="margin-top:20px;">
<a class="btn btn-secondary btn-small" href="facilities.php">Back to Facilities</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
