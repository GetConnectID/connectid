<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];
$csrfToken = csrfToken();

/*
|--------------------------------------------------------------------------
| Mark all notifications as read
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {

        $update = $pdo->prepare("
            UPDATE notifications
            SET read_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
            AND read_at IS NULL
        ");

        $update->execute([
            ':user_id' => $userId
        ]);

        header('Location: notifications.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Load notifications
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        type,
        title,
        message,
        reference_type,
        reference_id,
        read_at,
        created_at
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC, id DESC
    LIMIT 100
");

$stmt->execute([
    ':user_id' => $userId
]);

$notifications = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Count unread notifications
|--------------------------------------------------------------------------
*/

$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = :user_id
    AND read_at IS NULL
");

$unreadStmt->execute([
    ':user_id' => $userId
]);

$unreadCount = (int) $unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications | ConnectID</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #0b0b0b;
            color: #ffffff;
        }

        nav {
            padding: 18px 24px;
            border-bottom: 1px solid #222222;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            margin-right: 18px;
            font-size: 14px;
        }

        nav a:hover {
            color: #ff7a00;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0;
        }

        .unread {
            color: #ff7a00;
            font-size: 14px;
        }

        .card {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 14px;
            overflow: hidden;
        }

        .notification {
            padding: 20px;
            border-bottom: 1px solid #292929;
        }

        .notification:last-child {
            border-bottom: none;
        }

        .notification.unread {
            border-left: 3px solid #ff7a00;
        }

        .title {
            font-weight: 700;
            margin-bottom: 7px;
        }

        .message {
            color: #cccccc;
            line-height: 1.5;
        }

        .date {
            margin-top: 8px;
            color: #777777;
            font-size: 12px;
        }

        .empty {
            padding: 40px 20px;
            text-align: center;
            color: #888888;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            background: #333333;
            color: #ffffff;
            cursor: pointer;
        }

        button:hover {
            background: #444444;
        }

        @media (max-width: 650px) {
            .header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<nav>
    <a href="home.php">Home</a>
    <a href="profile.php">Profile</a>
    <a href="connections.php">Connections</a>
    <a href="messages.php">Messages</a>
    <a href="communities.php">Communities</a>
    <a href="notifications.php">Notifications</a>
    <a href="backend/logout.php">Logout</a>
</nav>

<div class="container">

    <div class="header">

        <div>
            <h1>Notifications</h1>

            <?php if ($unreadCount > 0): ?>

                <div class="unread">
                    <?= $unreadCount ?> unread
                </div>

            <?php endif; ?>

        </div>

        <?php if ($unreadCount > 0): ?>

            <form method="post">

                <input
                    type="hidden"
                    name="action"
                    value="mark_all_read"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken) ?>"
                >

                <button type="submit">
                    Mark all as read
                </button>

            </form>

        <?php endif; ?>

    </div>

    <div class="card">

        <?php if (!$notifications): ?>

            <div class="empty">
                You don't have any notifications yet.
            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>

                <div
                    class="notification <?= $notification['read_at'] === null ? 'unread' : '' ?>"
                >

                    <div class="title">
                        <?= htmlspecialchars($notification['title']) ?>
                    </div>

                    <div class="message">
                        <?= htmlspecialchars($notification['message']) ?>
                    </div>

                    <div class="date">
                        <?= htmlspecialchars($notification['created_at']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
