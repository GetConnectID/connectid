<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int) $_SESSION['user_id'];

$targetUsername = trim($_GET['user'] ?? '');
$afterId = max(0, (int) ($_GET['after_id'] ?? 0));

if ($targetUsername === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

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
        display_name
    FROM users
    WHERE username = :username
    AND status = 'active'
    LIMIT 1
");

$stmt->execute([
    ':username' => $targetUsername
]);

$targetUser = $stmt->fetch();

if (!$targetUser) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

    exit;
}

$targetUserId = (int) $targetUser['id'];

/*
|--------------------------------------------------------------------------
| Verify accepted connection
|--------------------------------------------------------------------------
*/

$connection = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE status = 'accepted'
    AND (
        (requester_id = :user_id AND receiver_id = :target_id)
        OR
        (requester_id = :target_id AND receiver_id = :user_id)
    )
    LIMIT 1
");

$connection->execute([
    ':user_id' => $userId,
    ':target_id' => $targetUserId
]);

if (!$connection->fetch()) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Load messages after requested ID
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.created_at
    FROM messages m
    WHERE
    (
        (
            m.sender_id = :user_id
            AND m.receiver_id = :target_id
        )
        OR
        (
            m.sender_id = :target_id
            AND m.receiver_id = :user_id
        )
    )
    AND m.id > :after_id
    ORDER BY m.id ASC
    LIMIT 100
");

$stmt->execute([
    ':user_id' => $userId,
    ':target_id' => $targetUserId,
    ':after_id' => $afterId
]);

$messages = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Escape message content for safe HTML insertion
|--------------------------------------------------------------------------
*/

foreach ($messages as &$message) {
    $message['message'] = nl2br(
        htmlspecialchars(
            $message['message'],
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

unset($message);

echo json_encode([
    'success' => true,
    'messages' => $messages
], JSON_UNESCAPED_UNICODE);

exit;
