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
$message = trim($_POST['message'] ?? '');

$receiverUsername = ltrim($receiverUsername, '@');

if (
    $receiverUsername === '' ||
    strlen($receiverUsername) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $receiverUsername)
) {
    header('Location: ../messages.php?error=invalid_user');
    exit;
}

if ($message === '') {
    header(
        'Location: ../messages.php?user=' .
        urlencode($receiverUsername) .
        '&error=empty_message'
    );
    exit;
}

if (mb_strlen($message) > 2000) {
    header(
        'Location: ../messages.php?user=' .
        urlencode($receiverUsername) .
        '&error=message_too_long'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Find receiver
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        status
    FROM users
    WHERE username = :receiver_username
    LIMIT 1
");

$stmt->execute([
    ':receiver_username' => $receiverUsername
]);

$receiver = $stmt->fetch();

if (
    !$receiver ||
    $receiver['status'] !== 'active'
) {
    header(
        'Location: ../messages.php?error=user_not_found'
    );
    exit;
}

$receiverId = (int) $receiver['id'];

if ($receiverId === $userId) {
    header(
        'Location: ../messages.php?user=' .
        urlencode($receiverUsername) .
        '&error=self_message'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify accepted connection
|--------------------------------------------------------------------------
*/

$connectionStmt = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE status = 'accepted'
    AND (
        (
            requester_id = :current_user_id
            AND receiver_id = :target_user_id
        )
        OR
        (
            requester_id = :target_user_id_reverse
            AND receiver_id = :current_user_reverse
        )
    )
    LIMIT 1
");

$connectionStmt->execute([
    ':current_user_id' => $userId,
    ':target_user_id' => $receiverId,
    ':target_user_id_reverse' => $receiverId,
    ':current_user_reverse' => $userId
]);

$connection = $connectionStmt->fetch();

if (!$connection) {
    header(
        'Location: ../messages.php?user=' .
        urlencode($receiverUsername) .
        '&error=not_connected'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Send message
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Return to conversation
|--------------------------------------------------------------------------
*/

header(
    'Location: ../messages.php?user=' .
    urlencode($receiverUsername)
);
exit;
