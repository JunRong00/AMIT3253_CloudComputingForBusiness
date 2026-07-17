<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$resourcePages = ['rooms.php', 'room_create.php', 'room_edit.php', 'equipment.php', 'equipment_create.php', 'equipment_edit.php', 'books.php', 'book_create.php', 'book_edit.php'];
$activityPages = ['schedule.php', 'bookings.php', 'loans.php', 'book_loans.php'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script>
(function () {
    var saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') {
        document.documentElement.setAttribute('data-theme', saved);
    }
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?></title>
<link rel="icon" type="image/png" href="../assets/favicon.png">
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../../style.css') ?>">
</head>
<body>
<nav class="navbar">
<a class="brand" href="rooms.php"><img src="../assets/tarumt-logo.png" alt="TAR UMT" class="brand-logo">Admin &middot; Academic &amp; Library Resource Scheduling</a>
<div class="nav-links">
<div class="nav-dropdown" data-nav-dropdown>
<button type="button" class="nav-dropdown-trigger <?= in_array($currentPage, $resourcePages) ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">Resources &#9662;</button>
<div class="nav-dropdown-menu">
<a href="rooms.php" class="<?= $currentPage === 'rooms.php' ? 'active' : '' ?>">Rooms</a>
<a href="equipment.php" class="<?= $currentPage === 'equipment.php' ? 'active' : '' ?>">Equipment</a>
<a href="books.php" class="<?= $currentPage === 'books.php' ? 'active' : '' ?>">Books</a>
</div>
</div>
<div class="nav-dropdown" data-nav-dropdown>
<button type="button" class="nav-dropdown-trigger <?= in_array($currentPage, $activityPages) ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">Activity &#9662;</button>
<div class="nav-dropdown-menu">
<a href="schedule.php" class="<?= $currentPage === 'schedule.php' ? 'active' : '' ?>">Schedule</a>
<a href="bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">Room Bookings</a>
<a href="loans.php" class="<?= $currentPage === 'loans.php' ? 'active' : '' ?>">Equipment Loans</a>
<a href="book_loans.php" class="<?= $currentPage === 'book_loans.php' ? 'active' : '' ?>">Book Loans</a>
</div>
</div>
<a href="testimonials.php" class="<?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">Testimonials</a>
<a href="messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">Messages</a>
<a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
<a href="../logout.php">Logout</a>
<button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle dark mode">&#9728;</button>
</div>
</nav>
<main class="container">
