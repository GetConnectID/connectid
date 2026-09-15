<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../connections.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$username = trim($_POST['username'] ?? '');
$username = ltrim($username, '@');

if (
    $username === '' ||
    !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)
) {
    header('Location: ../connections.php?error=invalid_username');
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

$target = $stmt->fetch();

if (!$target) {
    header('Location: ../connections.php?error=user_not_found');
    exit;
}

$targetId = (int) $target['id'];

if ($targetId === $userId) {
    header('Location: ../connections.php?error=self');
    exit;
}

$existing = $pdo->prepare("
    SELECT id, status
    FROM connections
    WHERE
        (requester_id = :user_a AND receiver_id = :user_b)
        OR
        (requester_id = :user_b2 AND receiver_id = :user_a2)
    LIMIT 1
");

$existing->execute([
    ':user_a' => $userId,
    ':user_b' => $targetId,
    ':user_b2' => $userId,
    ':user_a2' => $targetId
]);

$connection = $existing->fetch();

if ($connection) {
    header('Location: ../connections.php?error=already_exists');
    exit;
}

$insert = $pdo->prepare("
    INSERT INTO connections
    (
        requester_id,
        receiver_id,
        status
    )
    VALUES
    (
        :requester_id,
        :receiver_id,
        'pending'
    )
");

$insert->execute([
    ':requester_id' => $userId,
    ':receiver_id' => $targetId
]);

header('Location: ../connections.php?sent=1');
exit;
