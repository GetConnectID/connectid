<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/notification_service.php';

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
    strlen($username) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $username)
) {
    header('Location: ../connections.php?error=invalid_username');
    exit;
}

/*
|--------------------------------------------------------------------------
| Find target user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        status
    FROM users
    WHERE username = :username
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$targetUser = $stmt->fetch();

if (
    !$targetUser ||
    $targetUser['status'] !== 'active'
) {
    header('Location: ../connections.php?error=user_not_found');
    exit;
}

$targetUserId = (int) $targetUser['id'];

if ($targetUserId === $userId) {
    header('Location: ../connections.php?error=self');
    exit;
}

/*
|--------------------------------------------------------------------------
| Check existing connection in current direction
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        requester_id,
        receiver_id,
        status
    FROM connections
    WHERE requester_id = :requester_id
      AND receiver_id = :receiver_id
    LIMIT 1
");

$stmt->execute([
    ':requester_id' => $userId,
    ':receiver_id' => $targetUserId
]);

$connection = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Check existing connection in reverse direction
|--------------------------------------------------------------------------
*/

if (!$connection) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            requester_id,
            receiver_id,
            status
        FROM connections
        WHERE requester_id = :requester_id
          AND receiver_id = :receiver_id
        LIMIT 1
    ");

    $stmt->execute([
        ':requester_id' => $targetUserId,
        ':receiver_id' => $userId
    ]);

    $connection = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| Existing accepted connection
|--------------------------------------------------------------------------
*/

if (
    $connection &&
    $connection['status'] === 'accepted'
) {
    header('Location: ../connections.php?error=already_connected');
    exit;
}

/*
|--------------------------------------------------------------------------
| Existing pending request
|--------------------------------------------------------------------------
*/

if (
    $connection &&
    $connection['status'] === 'pending'
) {
    header('Location: ../connections.php?error=already_pending');
    exit;
}

/*
|--------------------------------------------------------------------------
| Create or reactivate connection
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    if (
        $connection &&
        $connection['status'] === 'declined'
    ) {

        $update = $pdo->prepare("
            UPDATE connections
            SET
                requester_id = :requester_id,
                receiver_id = :receiver_id,
                status = 'pending',
                created_at = CURRENT_TIMESTAMP
            WHERE id = :connection_id
        ");

        $update->execute([
            ':requester_id' => $userId,
            ':receiver_id' => $targetUserId,
            ':connection_id' => (int) $connection['id']
        ]);

        $connectionId = (int) $connection['id'];

    } else {

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
            ':receiver_id' => $targetUserId
        ]);

        $connectionId = (int) $pdo->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

    createNotification(
        $pdo,
        $targetUserId,
        'connection_request',
        'New connection request',
        '@' . $_SESSION['username'] . ' wants to connect with you.',
        'connection',
        $connectionId
    );

    $pdo->commit();

    header('Location: ../connections.php?sent=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../connections.php?error=connection_failed');
    exit;
}
