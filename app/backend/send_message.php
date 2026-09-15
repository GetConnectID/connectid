<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../messages.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$receiverUsername = trim($_POST['receiver_username'] ?? '');
$receiverUsername = ltrim($receiverUsername, '@');

$message = trim($_POST['message'] ?? '');

if (
    $receiverUsername === '' ||
    !preg_match('/^[A-Za-z0-9_]{3,30}$/', $receiverUsername)
) {
    header('Location: ../messages.php?error=invalid_user');
    exit;
}

if ($message === '' || strlen($message) > 5000) {
    header('Location: ../messages.php?error=invalid_message');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username
    FROM users
    WHERE username = :username
      AND status = 'active'
    LIMIT 1
");

$stmt->execute([
    ':username' => $receiverUsername
]);

$receiver = $stmt->fetch();

if (!$receiver) {
    header('Location: ../messages.php?error=user_not_found');
    exit;
}

$receiverId = (int) $receiver['id'];

if ($receiverId === $userId) {
    header('Location: ../messages.php?error=self');
    exit;
}

$connectionStmt = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE
        (
            requester_id = :user_a
            AND receiver_id = :user_b
        )
        OR
        (
            requester_id = :user_b2
            AND receiver_id = :user_a2
        )
    AND status = 'accepted'
    LIMIT 1
");

$connectionStmt->execute([
    ':user_a' => $userId,
    ':user_b' => $receiverId,
    ':user_b2' => $userId,
    ':user_a2' => $receiverId
]);

if (!$connectionStmt->fetch()) {
    header('Location: ../connections.php?error=not_connected');
    exit;
}

$insert = $pdo->prepare("
    INSERT INTO messages
    (
        sender_id,
        receiver_id,
        message
    )
    VALUES
    (
        :sender_id,
        :receiver_id,
        :message
    )
");

$insert->execute([
    ':sender_id' => $userId,
    ':receiver_id' => $receiverId,
    ':message' => $message
]);

header(
    'Location: ../messages.php?user=' .
    urlencode($receiver['username'])
);

exit;
