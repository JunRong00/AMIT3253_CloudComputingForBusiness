<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    die('Room not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_name = trim($_POST['room_name']);
    $location  = trim($_POST['location']);
    $capacity  = (int)$_POST['capacity'];

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'room');
    $image_url = $newImageUrl ?? $room['image_url'];

    if ($room_name === '' || $location === '' || $capacity < 1) {
        $error = 'Room name and location are required, and capacity must be at least 1.';
        $room = array_merge($room, compact('room_name', 'location', 'capacity', 'image_url'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($room['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE rooms SET room_name=?, location=?, capacity=?, image_url=? WHERE id=?');
        $stmt->bind_param('ssisi', $room_name, $location, $capacity, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: rooms.php');
        exit;
    }
}

$pageTitle = 'Edit Room';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Room</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$room['id'] ?>">
<label>Room Name <input type="text" name="room_name" value="<?= htmlspecialchars($room['room_name']) ?>" required></label>
<label>Location <input type="text" name="location" value="<?= htmlspecialchars($room['location']) ?>" required></label>
<label>Capacity <input type="number" name="capacity" min="1" value="<?= (int)$room['capacity'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($room)) ?>" alt="<?= htmlspecialchars($room['room_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Room</button>
</form>
<p><a class="btn btn-secondary btn-small" href="rooms.php">Back to rooms</a></p>
</div>
<?php require 'partials/footer.php'; ?>
