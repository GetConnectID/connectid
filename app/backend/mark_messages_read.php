<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$username = trim($_POST['username'] ?? '');
$username = ltrim($username, '@');

if (
    $username === '' ||
    strlen($username) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $username)
) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE username = :username
    AND status = 'active'
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$targetUserId = (int) $stmt->fetchColumn();

if (
    $targetUserId <= 0 ||
    $targetUserId === $userId
) {
    http_response_code(400);
    exit;
}

$update = $pdo->prepare("
    UPDATE messages
    SET read_at = CURRENT_TIMESTAMP
    WHERE receiver_id = :receiver_id
    AND sender_id = :sender_id
    AND read_at IS NULL
");

$update->execute([
    ':receiver_id' => $userId,
    ':sender_id' => $targetUserId
]);

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode([
    'success' => true
]);
