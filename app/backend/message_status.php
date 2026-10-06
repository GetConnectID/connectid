<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Total unread messages
|--------------------------------------------------------------------------
*/

$totalStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM messages
    WHERE receiver_id = :receiver_id
    AND read_at IS NULL
");

$totalStmt->execute([
    ':receiver_id' => $userId
]);

$totalUnread = (int) $totalStmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Unread messages per connection
|--------------------------------------------------------------------------
*/

$connectionsStmt = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.display_name,
        COUNT(m.id) AS unread_count
    FROM connections c

    INNER JOIN users u
        ON u.id = CASE
            WHEN c.requester_id = :current_user_id
            THEN c.receiver_id
            ELSE c.requester_id
        END

    LEFT JOIN messages m
        ON m.sender_id = u.id
        AND m.receiver_id = :message_receiver_id
        AND m.read_at IS NULL

    WHERE
        (
            c.requester_id = :connection_user_id
            OR c.receiver_id = :connection_user_id_2
        )
        AND c.status = 'accepted'
        AND u.status = 'active'

    GROUP BY
        u.id,
        u.username,
        u.display_name

    ORDER BY
        unread_count DESC,
        u.display_name ASC
");

$connectionsStmt->execute([
    ':current_user_id' => $userId,
    ':message_receiver_id' => $userId,
    ':connection_user_id' => $userId,
    ':connection_user_id_2' => $userId
]);

$connections = $connectionsStmt->fetchAll();

$unreadByUser = [];

foreach ($connections as $connection) {

    $count = (int) $connection['unread_count'];

    if ($count > 0) {
        $unreadByUser[$connection['username']] = $count;
    }
}

echo json_encode([
    'success' => true,
    'total_unread' => $totalUnread,
    'unread_by_user' => $unreadByUser
]);
