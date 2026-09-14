<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

$username = trim($_GET['user'] ?? '');
$username = ltrim($username, '@');

if ($username === '') {
    exit('User is required.');
}

$stmt = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE username = :username
     LIMIT 1'
);

$stmt->execute([
    'username' => strtolower($username)
]);

$otherUser = $stmt->fetch();

if (!$otherUser) {
    exit('User not found.');
}

$otherUserId = (int) $otherUser['id'];

$connection = $pdo->prepare(
    'SELECT id
     FROM connections
     WHERE status = "accepted"
     AND (
        (requester_id = :user_id AND receiver_id = :other_user_id)
        OR
        (requester_id = :other_user_id AND receiver_id = :user_id)
     )
     LIMIT 1'
);

$connection->execute([
    'user_id' => $userId,
    'other_user_id' => $otherUserId
]);

if (!$connection->fetch()) {
    http_response_code(403);
    exit('You are not connected with this user.');
}

$stmt = $pdo->prepare(
    'SELECT
        id,
        sender_id,
        receiver_id,
        message,
        created_at,
        read_at
     FROM messages
     WHERE
        (sender_id = :user_id AND receiver_id = :other_user_id)
        OR
        (sender_id = :other_user_id AND receiver_id = :user_id)
     ORDER BY created_at ASC'
);

$stmt->execute([
    'user_id' => $userId,
    'other_user_id' => $otherUserId
]);

$messages = $stmt->fetchAll();

foreach ($messages as &$message) {
    $message['message'] = htmlspecialchars(
        $message['message'],
        ENT_QUOTES,
        'UTF-8'
    );
}

header('Content-Type: application/json');

echo json_encode($messages);
