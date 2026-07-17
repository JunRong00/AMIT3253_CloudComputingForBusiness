<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM routes WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$route) {
    die('Route not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_name     = trim($_POST['route_name']);
    $origin         = trim($_POST['origin']);
    $destination    = trim($_POST['destination']);
    $departure_time = trim($_POST['departure_time']);
    $price          = (float)$_POST['price'];
    $total_seats    = (int)$_POST['total_seats'];

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'route');
    $image_url = $newImageUrl ?? $route['image_url'];

    if ($route_name === '' || $origin === '' || $destination === '' || $departure_time === '' || $total_seats < 1) {
        $error = 'All fields are required and total seats must be at least 1.';
        $route = array_merge($route, compact('route_name', 'origin', 'destination', 'departure_time', 'price', 'total_seats', 'image_url'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($route['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE routes SET route_name=?, origin=?, destination=?, departure_time=?, price=?, total_seats=?, image_url=? WHERE id=?');
        $stmt->bind_param('ssssdisi', $route_name, $origin, $destination, $departure_time, $price, $total_seats, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: routes.php');
        exit;
    }
}

$pageTitle = 'Edit Route';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Route</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$route['id'] ?>">
<label>Route Name <input type="text" name="route_name" value="<?= htmlspecialchars($route['route_name']) ?>" required></label>
<label>Origin <input type="text" name="origin" value="<?= htmlspecialchars($route['origin']) ?>" required></label>
<label>Destination <input type="text" name="destination" value="<?= htmlspecialchars($route['destination']) ?>" required></label>
<label>Departure Time <input type="time" name="departure_time" value="<?= htmlspecialchars($route['departure_time']) ?>" required></label>
<label>Price (RM) <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($route['price']) ?>" required></label>
<label>Total Seats <input type="number" name="total_seats" min="1" value="<?= (int)$route['total_seats'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($route)) ?>" alt="<?= htmlspecialchars($route['route_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Route</button>
</form>
<p><a class="btn btn-secondary btn-small" href="routes.php">Back to routes</a></p>
</div>
<?php require 'partials/footer.php'; ?>
