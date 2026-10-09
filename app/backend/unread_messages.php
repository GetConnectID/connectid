
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM messages
    WHERE receiver_id = :user_id
      AND read_at IS NULL
");

$stmt->execute([
    ':user_id' => $userId
]);

$count = (int) $stmt->fetchColumn();

echo json_encode([
    'unread' => $count
]);
