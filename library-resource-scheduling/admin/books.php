<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$books = $conn->query('SELECT * FROM books ORDER BY category, title');

$pageTitle = 'Manage Books';
require 'partials/header.php';
?>
<h1>Books</h1>
<p><a class="btn btn-small" href="book_create.php">+ Add Book</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Title</th><th>Author</th><th>Category</th><th>Copies</th><th>Actions</th></tr>
<?php while ($b = $books->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($b)) ?>" alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($b['title']) ?></td>
<td><?= htmlspecialchars($b['author']) ?></td>
<td><?= htmlspecialchars($b['category']) ?></td>
<td><?= (int)$b['total_copies'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="book_edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
<form action="book_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this book? Any existing loans for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
