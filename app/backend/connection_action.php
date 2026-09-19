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

$connectionId = (int) ($_POST['connection_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($connectionId <= 0) {
    header('Location: ../connections.php?error=invalid_connection');
    exit;
}

if (!in_array($action, ['accept', 'decline'], true)) {
    header('Location: ../connections.php?error=invalid_action');
    exit;
}

/*
|--------------------------------------------------------------------------
| Load pending request
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.requester_id,
        c.receiver_id,
        c.status,
        u.username,
        u.display_name
    FROM connections c
    JOIN users u
        ON u.id = c.requester_id
    WHERE c.id = :connection_id
    AND c.receiver_id = :user_id
    AND c.status = 'pending'
    LIMIT 1
");

$stmt->execute([
    ':connection_id' => $connectionId,
    ':user_id' => $userId
]);

$connection = $stmt->fetch();

if (!$connection) {
    header('Location: ../connections.php?error=connection_not_found');
    exit;
}

$requesterId = (int) $connection['requester_id'];

/*
|--------------------------------------------------------------------------
| Accept
|--------------------------------------------------------------------------
*/

if ($action === 'accept') {

    $update = $pdo->prepare("
        UPDATE connections
        SET status = 'accepted'
        WHERE id = :connection_id
    ");

    $update->execute([
        ':connection_id' => $connectionId
    ]);

    createNotification(
        $pdo,
        $requesterId,
        'connection_accepted',
        'Connection accepted',
        '@' . $_SESSION['username'] . ' accepted your connection request.',
        'connection',
        $connectionId
    );

    header('Location: ../connections.php?accepted=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Decline
|--------------------------------------------------------------------------
*/

$update = $pdo->prepare("
    UPDATE connections
    SET status = 'declined'
    WHERE id = :connection_id
");

$update->execute([
    ':connection_id' => $connectionId
]);

header('Location: ../connections.php?declined=1');
exit;
