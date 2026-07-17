<?php
require '../config.php';
require '../auth.php';
require_admin();

$facility_id = (int)($_GET['facility_id'] ?? $_POST['facility_id'] ?? 0);
$error = '';

$stmt = $conn->prepare('SELECT * FROM facilities WHERE id = ?');
$stmt->bind_param('i', $facility_id);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$facility) {
    die('Facility not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name === '') {
        $error = 'Court name is required.';
    } else {
        $stmt = $conn->prepare('INSERT INTO courts (facility_id, name) VALUES (?, ?)');
        $stmt->bind_param('is', $facility_id, $name);
        $stmt->execute();
        $stmt->close();
        header('Location: facility_edit.php?id=' . $facility_id);
        exit;
    }
}

$pageTitle = 'Add Court';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Court</h1>
<p class="stat-label">Facility: <?= htmlspecialchars($facility['name']) ?></p>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="facility_id" value="<?= (int)$facility_id ?>">
<label>Court Name <input type="text" name="name" placeholder="e.g. Court 3, Lane 5" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></label>
<button type="submit">Add Court</button>
</form>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="facility_edit.php?id=<?= (int)$facility_id ?>">Back to Facility</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
