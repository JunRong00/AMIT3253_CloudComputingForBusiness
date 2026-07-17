<?php
require '../config.php';
require '../auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare('SELECT facility_id FROM courts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $court = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($court) {
        $stmt = $conn->prepare('DELETE FROM courts WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $_SESSION['flash_error'] = 'Cannot delete this court: it still has bookings or closures referencing it.';
        }
        $stmt->close();

        header('Location: facility_edit.php?id=' . $court['facility_id']);
        exit;
    }
}

header('Location: facilities.php');
exit;
