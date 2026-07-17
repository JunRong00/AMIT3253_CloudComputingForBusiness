<?php
session_start();

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_name() {
    return $_SESSION['user_name'] ?? null;
}

function current_user_is_admin() {
    return !empty($_SESSION['is_admin']);
}

function require_login() {
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }

    // A session can outlive the account it points to (e.g. the account was
    // deleted or the database was reset). Catch that here instead of letting
    // every write below fail a foreign-key check silently.
    global $conn;
    $stmt = $conn->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exists) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!current_user_is_admin()) {
        http_response_code(403);
        die('Forbidden: admin access only.');
    }
}
