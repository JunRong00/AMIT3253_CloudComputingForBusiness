<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare('SELECT image_url FROM facilities WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $facility = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM facilities WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($facility) {
            delete_facility_image_file($facility['image_url'], __DIR__ . '/../uploads');
        }
    } else {
        $_SESSION['flash_error'] = 'Cannot delete this facility: it still has bookings or closures referencing it.';
    }
    $stmt->close();
}

header('Location: facilities.php');
exit;
