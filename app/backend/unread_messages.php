<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

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

$unreadMessages = (int) $stmt->fetchColumn();

return $unreadMessages;
