<?php
require '../config.php';
require '../auth.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';

$stmt = $conn->prepare('
    SELECT c.id, c.name, c.facility_id, f.name AS facility_name
    FROM courts c
    JOIN facilities f ON f.id = c.facility_id
    WHERE c.id = ?
');
$stmt->bind_param('i', $id);
$stmt->execute();
$court = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$court) {
    die('Court not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name === '') {
        $error = 'Court name is required.';
        $court['name'] = $name;
    } else {
        $stmt = $conn->prepare('UPDATE courts SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $name, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: facility_edit.php?id=' . $court['facility_id']);
        exit;
    }
}

$pageTitle = 'Edit Court';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Court</h1>
<p class="stat-label">Facility: <?= htmlspecialchars($court['facility_name']) ?></p>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$court['id'] ?>">
<label>Court Name <input type="text" name="name" value="<?= htmlspecialchars($court['name']) ?>" required></label>
<button type="submit">Update Court</button>
</form>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="facility_edit.php?id=<?= (int)$court['facility_id'] ?>">Back to Facility</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
