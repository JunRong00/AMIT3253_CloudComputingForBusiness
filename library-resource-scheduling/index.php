<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare('SELECT * FROM rooms WHERE room_name LIKE ? ORDER BY room_name');
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param('s', $likeSearch);
    $stmt->execute();
    $rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rooms = $conn->query('SELECT * FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);
}

$myBookings = [];
$myLoans = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('
        SELECT b.id, r.room_name, b.booking_date, b.time_slot, b.purpose
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date, b.time_slot
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('
        SELECT l.id, e.equipment_name, l.loan_date, l.time_slot, l.quantity, l.purpose, l.returned_at, l.fine_paid_at
        FROM equipment_loans l
        JOIN equipment e ON e.id = l.equipment_id
        WHERE l.user_id = ?
        ORDER BY l.returned_at IS NOT NULL, l.loan_date, l.time_slot
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myLoans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('
        SELECT bl.id, bk.title, bk.author, bl.checkout_date, bl.due_date, bl.returned_at, bl.fine_paid_at
        FROM book_loans bl
        JOIN books bk ON bk.id = bl.book_id
        WHERE bl.user_id = ?
        ORDER BY bl.returned_at IS NOT NULL, bl.due_date
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myBookLoans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'Academic & Library Resource Scheduling';
require 'partials/header.php';
?>
<section class="hero">
<h1>Academic &amp; Library Resource Scheduling</h1>
<p>Reserve a discussion room or loan academic equipment for your group work.</p>
</section>

<section>
<h2>Available Rooms</h2>
<form method="get" class="filter-bar" id="room-filter-form">
<label>Search <input type="text" name="q" id="room-search" placeholder="Room name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('room-search');
    var form = document.getElementById('room-filter-form');
    if (!input || !form) return;
    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.submit();
        }, 500);
    });
})();
</script>

<?php if (empty($rooms)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No rooms match your search.</p>
<a class="btn btn-small btn-secondary" href="index.php">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($rooms as $r): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($r)) ?>" alt="<?= htmlspecialchars($r['room_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($r['room_name']) ?></h3>
<p><?= htmlspecialchars($r['location']) ?></p>
<p>Capacity: <?= (int)$r['capacity'] ?></p>
<?php if (current_user_id()): ?>
<a class="btn" href="create.php?room_id=<?= (int)$r['id'] ?>">Book Now</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<?php $equipment = $conn->query('SELECT * FROM equipment ORDER BY category, equipment_name')->fetch_all(MYSQLI_ASSOC); ?>
<section>
<h2>Available Equipment</h2>
<?php if (empty($equipment)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128187;</div>
<p>No equipment available right now.</p>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($equipment as $e): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['equipment_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($e['equipment_name']) ?></h3>
<p><?= htmlspecialchars($e['category']) ?></p>
<p>Units available: <?= (int)$e['total_units'] ?></p>
<?php if (current_user_id()): ?>
<a class="btn" href="loan_create.php?equipment_id=<?= (int)$e['id'] ?>">Loan Now</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Loan</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<?php
$books = $conn->query('
    SELECT bk.*, bk.total_copies - COALESCE(active.active_count, 0) AS copies_available
    FROM books bk
    LEFT JOIN (
        SELECT book_id, COUNT(*) AS active_count
        FROM book_loans
        WHERE returned_at IS NULL
        GROUP BY book_id
    ) active ON active.book_id = bk.id
    ORDER BY bk.category, bk.title
')->fetch_all(MYSQLI_ASSOC);
?>
<section>
<h2>Available Books</h2>
<?php if (empty($books)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128218;</div>
<p>No books available right now.</p>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($books as $b): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($b)) ?>" alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
<h3><?= htmlspecialchars($b['title']) ?></h3>
<p><?= htmlspecialchars($b['author']) ?></p>
<p>Copies available: <?= (int)$b['copies_available'] ?> / <?= (int)$b['total_copies'] ?></p>
<?php if (current_user_id()): ?>
<?php if ($b['copies_available'] > 0): ?>
<a class="btn" href="book_loan_create.php?book_id=<?= (int)$b['id'] ?>">Borrow Now</a>
<?php else: ?>
<span class="badge badge-critical">All copies on loan</span>
<?php endif; ?>
<?php else: ?>
<a class="btn" href="login.php">Login to Borrow</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Bookings</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your bookings.</p>
<?php elseif (empty($myBookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>You have no bookings yet. Book a room above to get started.</p>
</div>
<?php else: ?>
<table>
<tr><th>Room</th><th>Date</th><th>Time Slot</th><th>Purpose</th><th>Actions</th></tr>
<?php foreach ($myBookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['room_name']) ?></td>
<td><?= htmlspecialchars($b['booking_date']) ?></td>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['purpose']) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>

<section>
<h2>My Equipment Loans</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your loans.</p>
<?php elseif (empty($myLoans)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128187;</div>
<p>You have no equipment loans yet. Loan an item above to get started.</p>
</div>
<?php else: ?>
<table>
<tr><th>Equipment</th><th>Date</th><th>Time Slot</th><th>Qty</th><th>Purpose</th><th>Status</th><th>Fine</th><th>Actions</th></tr>
<?php foreach ($myLoans as $l): ?>
<?php
$isReturned = $l['returned_at'] !== null;
$fine = equipment_fine_amount($l['loan_date'], $l['time_slot'], $l['returned_at']);
$isOverdue = !$isReturned && $fine > 0;
?>
<tr>
<td><?= htmlspecialchars($l['equipment_name']) ?></td>
<td><?= htmlspecialchars($l['loan_date']) ?></td>
<td><?= htmlspecialchars($l['time_slot']) ?></td>
<td><?= (int)$l['quantity'] ?></td>
<td><?= htmlspecialchars($l['purpose']) ?></td>
<td>
<?php if ($isReturned): ?>
<span class="badge badge-good">Returned</span>
<?php elseif ($isOverdue): ?>
<span class="badge badge-critical">Overdue</span>
<?php else: ?>
<span class="badge badge-neutral">On Loan</span>
<?php endif; ?>
</td>
<td>
<?php if ($fine <= 0): ?>
&mdash;
<?php elseif ($l['fine_paid_at']): ?>
<span class="badge badge-good">Paid</span>
<?php else: ?>
RM<?= number_format($fine, 2) ?>
<?php endif; ?>
</td>
<td>
<?php if (!$isReturned): ?>
<a class="btn btn-secondary btn-small" href="loan_edit.php?id=<?= (int)$l['id'] ?>">Edit</a>
<form action="loan_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this loan?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
<form action="loan_return.php" method="post" style="display:inline" onsubmit="return confirm('Mark this equipment as returned?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn btn-secondary btn-small">Return</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>

<section>
<h2>My Book Loans</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your book loans.</p>
<?php elseif (empty($myBookLoans)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128218;</div>
<p>You have no book loans yet. Borrow a book above to get started.</p>
</div>
<?php else: ?>
<table>
<tr><th>Book</th><th>Author</th><th>Checked Out</th><th>Due Back</th><th>Status</th><th>Fine</th><th>Actions</th></tr>
<?php foreach ($myBookLoans as $l): ?>
<?php
$isReturned = $l['returned_at'] !== null;
$isOverdue = !$isReturned && $l['due_date'] < date('Y-m-d');
$fine = book_fine_amount($l['due_date'], $l['returned_at']);
?>
<tr>
<td><?= htmlspecialchars($l['title']) ?></td>
<td><?= htmlspecialchars($l['author']) ?></td>
<td><?= htmlspecialchars($l['checkout_date']) ?></td>
<td><?= htmlspecialchars($l['due_date']) ?></td>
<td>
<?php if ($isReturned): ?>
<span class="badge badge-good">Returned</span>
<?php elseif ($isOverdue): ?>
<span class="badge badge-critical">Overdue</span>
<?php else: ?>
<span class="badge badge-neutral">On Loan</span>
<?php endif; ?>
</td>
<td>
<?php if ($fine <= 0): ?>
&mdash;
<?php elseif ($l['fine_paid_at']): ?>
<span class="badge badge-good">Paid</span>
<?php else: ?>
RM<?= number_format($fine, 2) ?>
<?php endif; ?>
</td>
<td>
<?php if (!$isReturned): ?>
<form action="book_loan_return.php" method="post" style="display:inline" onsubmit="return confirm('Mark this book as returned?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn btn-secondary btn-small">Return</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
