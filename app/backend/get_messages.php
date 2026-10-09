<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$userId = (int) $_SESSION['user_id'];

$targetUsername = trim($_GET['user'] ?? '');
$afterId = (int) ($_GET['after_id'] ?? 0);

$targetUsername = ltrim($targetUsername, '@');

if (
    $targetUsername === '' ||
    strlen($targetUsername) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $targetUsername)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

    exit;
}

if ($afterId < 0) {
    $afterId = 0;
}

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

if ($targetUserId === $userId) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Verify accepted connection
|--------------------------------------------------------------------------
*/

$connectionStmt = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE status = 'accepted'
    AND
    (
        (
            requester_id = :current_user_id
            AND receiver_id = :target_user_id
        )
        OR
        (
            requester_id = :target_user_id_2
            AND receiver_id = :current_user_id_2
        )
    )
    LIMIT 1
");

$connectionStmt->execute([
    ':current_user_id' => $userId,
    ':target_user_id' => $targetUserId,
    ':target_user_id_2' => $targetUserId,
    ':current_user_id_2' => $userId
]);

if (!$connectionStmt->fetch()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'messages' => []
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Load messages
|--------------------------------------------------------------------------
*/

$messageStmt = $pdo->prepare("
    SELECT
        m.id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.created_at,
        m.read_at,
        u.username,
        u.display_name
    FROM messages m
    INNER JOIN users u
        ON u.id = m.sender_id
    WHERE
        (
            (
                m.sender_id = :user_id
                AND m.receiver_id = :target_id
            )
            OR
            (
                m.sender_id = :target_id_2
                AND m.receiver_id = :user_id_2
            )
        )
        AND m.id > :after_id
    ORDER BY m.id ASC
    LIMIT 100
");

$messageStmt->execute([
    ':user_id' => $userId,
    ':target_id' => $targetUserId,
    ':target_id_2' => $targetUserId,
    ':user_id_2' => $userId,
    ':after_id' => $afterId
]);

$messages = $messageStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Mark incoming messages that were just loaded as read
|--------------------------------------------------------------------------
*/

if ($messages) {

    $messageIds = [];

    foreach ($messages as $message) {
        if (
            (int) $message['receiver_id'] === $userId &&
            (int) $message['sender_id'] === $targetUserId
        ) {
            $messageIds[] = (int) $message['id'];
        }
    }

    if ($messageIds) {

        $placeholders = implode(
            ',',
            array_fill(0, count($messageIds), '?')
        );

        $markRead = $pdo->prepare("
            UPDATE messages
            SET read_at = CURRENT_TIMESTAMP
            WHERE id IN ($placeholders)
            AND receiver_id = ?
            AND sender_id = ?
            AND read_at IS NULL
        ");

        $params = $messageIds;
        $params[] = $userId;
        $params[] = $targetUserId;

        $markRead->execute($params);
    }
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
