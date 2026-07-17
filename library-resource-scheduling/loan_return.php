<?php
require 'config.php';
require 'auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)$_POST['id'];
    $uid = current_user_id();

    $stmt = $conn->prepare('UPDATE equipment_loans SET returned_at = NOW() WHERE id = ? AND user_id = ? AND returned_at IS NULL');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $stmt->close();
}

header('Location: index.php');
exit;
