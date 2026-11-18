<?php
// posts.php – ACTIVITY 3 forum page (lists posts and lets you create one).
// Requires your auth.php from Activity 2.
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim((string) $_POST['title']);
    $body  = trim((string) ($_POST['body'] ?? ''));
    if ($title !== '' && $body !== '') {
        $uid = (int) $_SESSION['user_id'];           // owner = the logged-in user
        $stmt = $conn->prepare('INSERT INTO posts (user_id, title, body) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $uid, $title, $body);
        $stmt->execute();
        flash('Post published.');
    }
    header('Location: posts.php');
    exit;
}

$posts = $conn->query(
    'SELECT p.id, p.user_id, p.title, p.body, p.created_at, u.username
     FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC'
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Forum';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Forum</h1>

    <form method="post" class="stack">
        <input type="text" name="title" placeholder="Title" maxlength="150" required>
        <textarea name="body" rows="3" placeholder="Write something…" required></textarea>
        <button type="submit">Post</button>
    </form>

    <?php foreach ($posts as $p): ?>
        <article class="post">
            <header>
                <strong>#<?= (int) $p['id'] ?> · <?= e($p['title']) ?></strong>
                <span class="muted">by <?= e($p['username']) ?> · <?= e($p['created_at']) ?></span>
            </header>
            <p><?= nl2br(e($p['body'])) ?></p>

            <?php
            // FIXED: Delete is a POST form with a CSRF token, shown only to the owner or an admin.
            // Hiding the button is only for usability; delete_post.php does the real check.
            $canDelete = (int) $p['user_id'] === (int) $_SESSION['user_id']
                      || ($_SESSION['role'] ?? '') === 'admin';
            ?>
            <?php if ($canDelete): ?>
                <form method="post" action="delete_post.php" onsubmit="return confirm('Delete this post?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="btn-small danger-btn">Delete</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (!$posts): ?><p class="muted">No posts yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
