<?php // delete_post.php (secure version)
declare(strict_types=1);
require __DIR__ . '/auth.php';          // db + helpers + hardened session_start()
require_login();                        // FIX 1: logged-out visitors are sent to login.php
$uid = (int) $_SESSION['user_id'];
$ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
function audit(mysqli $conn, int $uid, string $action, string $details, string $ip): void
{   // FIX 5: accounting – every attempt is written to audit_logs
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address)
                            VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $uid, $action, $details, $ip);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {        // FIX 4: deleting needs a POST, never a GET link
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$token = (string) ($_POST['csrf_token'] ?? '');     // FIX 4: CSRF token must match the session
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    audit($conn, $uid, 'DELETE_POST_DENIED', 'Bad or missing CSRF token', $ip);
    http_response_code(403);
    exit('Invalid request. Reload the page and try again.');
}

$post_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$post_id) {                                     // FIX 6: validate input (whole number >= 1)
    http_response_code(400);
    exit('Invalid post ID.');
}
// FIX 2 + 3: prepared statement + ownership in the WHERE clause (admin may moderate)
if (($_SESSION['role'] ?? '') === 'admin') {
    $stmt = $conn->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->bind_param('i', $post_id);
} else {
    $stmt = $conn->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $post_id, $uid);
}
$stmt->execute();
if ($stmt->affected_rows === 1) {
    audit($conn, $uid, 'DELETE_POST', "Deleted post #$post_id", $ip);
    flash('Post deleted.');
} else {                                             // not found OR not the owner – same answer
    audit($conn, $uid, 'DELETE_POST_DENIED', "Not owner or no such post #$post_id", $ip);
    flash('You can only delete your own posts.');
}
header('Location: ' . BASE_URL . '/posts.php');
exit;                                                // FIX 6: always stop after a redirect
