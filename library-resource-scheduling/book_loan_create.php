<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$error = '';
$selectedBook = (int)($_GET['book_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $uid     = current_user_id();
    $selectedBook = $book_id;

    $conn->begin_transaction();

    $stmt = $conn->prepare('SELECT title, total_copies FROM books WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book) {
        $error = 'Book not found.';
        $conn->rollback();
    } else {
        $stmt = $conn->prepare('SELECT COUNT(*) AS active_count FROM book_loans WHERE book_id = ? AND returned_at IS NULL');
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $activeCount = (int)$stmt->get_result()->fetch_assoc()['active_count'];
        $stmt->close();

        if ($activeCount >= $book['total_copies']) {
            $error = 'All copies of this book are currently on loan.';
            $conn->rollback();
        } else {
            $checkoutDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            $stmt = $conn->prepare('INSERT INTO book_loans (user_id, book_id, checkout_date, due_date) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiss', $uid, $book_id, $checkoutDate, $dueDate);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            header('Location: index.php');
            exit;
        }
    }
}

$books = $conn->query('SELECT * FROM books ORDER BY category, title');

$pageTitle = 'Borrow a Book';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Borrow a Book</h1>
<p>Loan period is 2 weeks from today.</p>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<label>Book
<select name="book_id" required>
<?php while ($b = $books->fetch_assoc()): ?>
<option value="<?= (int)$b['id'] ?>" <?= $b['id'] == $selectedBook ? 'selected' : '' ?>><?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?></option>
<?php endwhile; ?>
</select>
</label>
<p class="stat-label">Checkout date: <?= htmlspecialchars(date('d M Y')) ?> &middot; Due back: <?= htmlspecialchars(date('d M Y', strtotime('+14 days'))) ?></p>
<button type="submit">Borrow Now</button>
</form>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
