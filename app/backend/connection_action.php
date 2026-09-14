<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

$connectionId = (int) ($_POST['connection_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($connectionId <= 0) {
    exit('Invalid connection request.');
}

if (!in_array($action, ['accept', 'decline'], true)) {
    exit('Invalid action.');
}

$stmt = $pdo->prepare(
    'SELECT id, requester_id, receiver_id, status
     FROM connections
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $connectionId
]);

$connection = $stmt->fetch();

if (!$connection) {
    exit('Connection request not found.');
}

if ((int) $connection['receiver_id'] !== (int) $userId) {
    http_response_code(403);
    exit('You are not allowed to modify this request.');
}

if ($connection['status'] !== 'pending') {
    exit('This request is no longer pending.');
}

$newStatus = $action === 'accept'
    ? 'accepted'
    : 'declined';

$update = $pdo->prepare(
    'UPDATE connections
     SET status = :status
     WHERE id = :id'
);

$update->execute([
    'status' => $newStatus,
    'id' => $connectionId
]);

header('Location: ../connections.php');
exit;
