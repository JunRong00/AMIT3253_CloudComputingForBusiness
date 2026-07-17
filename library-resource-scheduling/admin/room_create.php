<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_name = trim($_POST['room_name']);
    $location  = trim($_POST['location']);
    $capacity  = (int)$_POST['capacity'];

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'room');

    if ($room_name === '' || $location === '' || $capacity < 1) {
        $error = 'Room name and location are required, and capacity must be at least 1.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO rooms (room_name, location, capacity, image_url) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssis', $room_name, $location, $capacity, $image_url);
        $stmt->execute();
        $stmt->close();
        header('Location: rooms.php');
        exit;
    }
}

$pageTitle = 'Add Room';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Room</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Room Name <input type="text" name="room_name" required></label>
<label>Location <input type="text" name="location" required></label>
<label>Capacity <input type="number" name="capacity" min="1" required></label>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Room</button>
</form>
<p><a class="btn btn-secondary btn-small" href="rooms.php">Back to rooms</a></p>
</div>
<?php require 'partials/footer.php'; ?>
