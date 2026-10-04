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

try {

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

    /*
    |--------------------------------------------------------------------------
    | Prevent self connection
    |--------------------------------------------------------------------------
    */

    if ($targetUserId === $userId) {
        header('Location: ../connections.php?error=self');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check existing connection
    |--------------------------------------------------------------------------
    */

    $existing = $pdo->prepare("
        SELECT
            id,
            requester_id,
            receiver_id,
            status
        FROM connections
        WHERE
            (
                requester_id = :current_user_id
                AND receiver_id = :target_user_id
            )
            OR
            (
                requester_id = :target_user_id
                AND receiver_id = :current_user_id
            )
        LIMIT 1
    ");

    $existing->execute([
        ':current_user_id' => $userId,
        ':target_user_id' => $targetUserId
    ]);

    $connection = $existing->fetch();

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
    | Start transaction
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Re-use declined connection
    |--------------------------------------------------------------------------
    */

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

        /*
        |--------------------------------------------------------------------------
        | Create new connection request
        |--------------------------------------------------------------------------
        */

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
    | Create notification
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

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    header('Location: ../connections.php?sent=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
    |--------------------------------------------------------------------------
    | Never expose database errors to the user
    |--------------------------------------------------------------------------
    */

    header('Location: ../connections.php?error=connection_failed');
    exit;
}
