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
$action = trim($_POST['action'] ?? '');

if (
    $connectionId <= 0 ||
    !in_array($action, ['accept', 'decline'], true)
) {
    header('Location: ../connections.php?error=invalid');
    exit;
}

/*
|--------------------------------------------------------------------------
| Find pending connection request
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        requester_id,
        receiver_id,
        status
    FROM connections
    WHERE id = :connection_id
    LIMIT 1
");

$stmt->execute([
    ':connection_id' => $connectionId
]);

$connection = $stmt->fetch();

if (!$connection) {
    header('Location: ../connections.php?error=unauthorized');
    exit;
}

/*
|--------------------------------------------------------------------------
| Make sure the logged-in user is the receiver
|--------------------------------------------------------------------------
*/

if (
    (int) $connection['receiver_id'] !== $userId ||
    $connection['status'] !== 'pending'
) {
    header('Location: ../connections.php?error=unauthorized');
    exit;
}

$requesterId = (int) $connection['requester_id'];

/*
|--------------------------------------------------------------------------
| Accept connection
|--------------------------------------------------------------------------
*/

if ($action === 'accept') {

    $update = $pdo->prepare("
        UPDATE connections
        SET status = 'accepted'
        WHERE id = :connection_id
          AND receiver_id = :receiver_id
          AND status = 'pending'
    ");

    $update->execute([
        ':connection_id' => $connectionId,
        ':receiver_id' => $userId
    ]);

    if ($update->rowCount() !== 1) {
        header('Location: ../connections.php?error=update');
        exit;
    }

    createNotification(
        $pdo,
        $requesterId,
        'connection',
        'Connection accepted',
        ($_SESSION['display_name'] ?? $_SESSION['username']) .
        ' accepted your connection request.',
        'connection',
        $connectionId
    );

    header('Location: ../connections.php?success=accepted');
    exit;
}

/*
|--------------------------------------------------------------------------
| Decline connection
|--------------------------------------------------------------------------
*/

$update = $pdo->prepare("
    UPDATE connections
    SET status = 'declined'
    WHERE id = :connection_id
      AND receiver_id = :receiver_id
      AND status = 'pending'
");

$update->execute([
    ':connection_id' => $connectionId,
    ':receiver_id' => $userId
]);

if ($update->rowCount() !== 1) {
    header('Location: ../connections.php?error=update');
    exit;
}

createNotification(
    $pdo,
    $requesterId,
    'connection',
    'Connection request declined',
    ($_SESSION['display_name'] ?? $_SESSION['username']) .
    ' declined your connection request.',
    'connection',
    $connectionId
);

header('Location: ../connections.php?success=declined');
exit;
