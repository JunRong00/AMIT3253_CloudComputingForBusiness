<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$books = $conn->query('
    SELECT b.*, b.total_copies - COALESCE(active.active_count, 0) AS copies_available
    FROM books b
    LEFT JOIN (
        SELECT book_id, COUNT(*) AS active_count
        FROM book_loans
        WHERE returned_at IS NULL
        GROUP BY book_id
    ) active ON active.book_id = b.id
    ORDER BY b.category, b.title
')->fetch_all(MYSQLI_ASSOC);

$totalTitles = count($books);
$totalCopies = array_sum(array_column($books, 'total_copies'));

$pageTitle = 'All Books';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Books</h1>
<p>Borrow a book for up to 2 weeks at a time.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalTitles ?></h3><p>Titles available</p></div>
<div class="card"><h3><?= (int)$totalCopies ?></h3><p>Total copies</p></div>
</div>
</section>

<?php foreach ($books as $b): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($b)) ?>" alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
<h3><?= htmlspecialchars($b['title']) ?></h3>
<p>&#128100; <?= htmlspecialchars($b['author']) ?></p>
<p>&#128193; <?= htmlspecialchars($b['category']) ?></p>
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
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
