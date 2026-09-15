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

$newStatus = $action === 'accept'
    ? 'accepted'
    : 'declined';

$stmt = $pdo->prepare("
    UPDATE connections
    SET
        status = :status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = :id
      AND receiver_id = :receiver_id
      AND status = 'pending'
");

$stmt->execute([
    ':status' => $newStatus,
    ':id' => $connectionId,
    ':receiver_id' => $userId
]);

header('Location: ../connections.php');
exit;
