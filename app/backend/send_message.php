<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../messages.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$targetUsername = trim($_POST['receiver_username'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($targetUsername === '' || $message === '') {
    header('Location: ../messages.php?error=missing');
    exit;
}

if (mb_strlen($message) > 2000) {
    header('Location: ../messages.php?error=too_long');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name
    FROM users
    WHERE username = :username
    AND status = 'active'
    LIMIT 1
");

$stmt->execute([
    ':username' => $targetUsername
]);

$targetUser = $stmt->fetch();

if (!$targetUser) {
    header('Location: ../messages.php?error=user');
    exit;
}

$targetUserId = (int) $targetUser['id'];

if ($targetUserId === $userId) {
    header('Location: ../messages.php?user=' . urlencode($targetUsername) . '&error=self');
    exit;
}

$connection = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE status = 'accepted'
    AND (
        (requester_id = :user_id AND receiver_id = :target_id)
        OR
        (requester_id = :target_id AND receiver_id = :user_id)
    )
    LIMIT 1
");

$connection->execute([
    ':user_id' => $userId,
    ':target_id' => $targetUserId
]);

if (!$connection->fetch()) {
    header('Location: ../messages.php?user=' . urlencode($targetUsername) . '&error=not_connected');
    exit;
}

$pdo->beginTransaction();

try {

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
        ':receiver_id' => $targetUserId,
        ':message' => $message
    ]);

    $messageId = (int) $pdo->lastInsertId();

    $senderName = $_SESSION['display_name'] ?? $_SESSION['username'];

    createNotification(
        $pdo,
        $targetUserId,
        'message',
        'New message',
        $senderName . ' sent you a message.',
        'message',
        $messageId
    );

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../messages.php?user=' . urlencode($targetUsername) . '&error=send');
    exit;
}

header('Location: ../messages.php?user=' . urlencode($targetUsername));
exit;
