<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

$receiverUsername = trim($_POST['username'] ?? '');
$message = trim($_POST['message'] ?? '');

$receiverUsername = ltrim($receiverUsername, '@');

if ($receiverUsername === '' || $message === '') {
    exit('Username and message are required.');
}

if (strlen($message) > 5000) {
    exit('Message is too long.');
}

$stmt = $pdo->prepare(
    'SELECT id, status
     FROM users
     WHERE username = :username
     LIMIT 1'
);

$stmt->execute([
    'username' => strtolower($receiverUsername)
]);

$receiver = $stmt->fetch();

if (!$receiver) {
    exit('User not found.');
}

if ((int) $receiver['id'] === (int) $userId) {
    exit('You cannot send a message to yourself.');
}

if ($receiver['status'] !== 'active') {
    exit('This user is not active.');
}

$connection = $pdo->prepare(
    'SELECT id
     FROM connections
     WHERE status = "accepted"
     AND (
        (requester_id = :user_id AND receiver_id = :receiver_id)
        OR
        (requester_id = :receiver_id AND receiver_id = :user_id)
     )
     LIMIT 1'
);

$connection->execute([
    'user_id' => $userId,
    'receiver_id' => $receiver['id']
]);

if (!$connection->fetch()) {
    exit('You can only message an accepted connection.');
}

$insert = $pdo->prepare(
    'INSERT INTO messages
     (sender_id, receiver_id, message)
     VALUES
     (:sender_id, :receiver_id, :message)'
);

$insert->execute([
    'sender_id' => $userId,
    'receiver_id' => $receiver['id'],
    'message' => $message
]);

header(
    'Location: ../messages.php?user=' .
    urlencode($receiver['username'])
);

exit;
