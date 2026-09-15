<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$selectedUsername = trim($_GET['user'] ?? '');
$selectedUsername = ltrim($selectedUsername, '@');

$selectedUser = null;
$messages = [];

if ($selectedUsername !== '') {

    $userStmt = $pdo->prepare("
        SELECT
            id,
            username,
            display_name,
            role,
            reputation
        FROM users
        WHERE username = :username
          AND status = 'active'
        LIMIT 1
    ");

    $userStmt->execute([
        ':username' => $selectedUsername
    ]);

    $selectedUser = $userStmt->fetch();

    if ($selectedUser) {

        $connectionStmt = $pdo->prepare("
            SELECT id
            FROM connections
            WHERE
                (
                    requester_id = :user_a
                    AND receiver_id = :user_b
                )
                OR
                (
                    requester_id = :user_b2
                    AND receiver_id = :user_a2
                )
            AND status = 'accepted'
            LIMIT 1
        ");

        $connectionStmt->execute([
            ':user_a' => $userId,
            ':user_b' => $selectedUser['id'],
            ':user_b2' => $userId,
            ':user_a2' => $selectedUser['id']
        ]);

        $connection = $connectionStmt->fetch();

        if ($connection) {

            $messageStmt = $pdo->prepare("
                SELECT
                    id,
                    sender_id,
                    receiver_id,
                    message,
                    created_at,
                    read_at
                FROM messages
                WHERE
                    (
                        sender_id = :user_a
                        AND receiver_id = :user_b
                    )
                    OR
                    (
                        sender_id = :user_b2
                        AND receiver_id = :user_a2
                    )
                ORDER BY created_at ASC
            ");

            $messageStmt->execute([
                ':user_a' => $userId,
                ':user_b' => $selectedUser['id'],
                ':user_b2' => $selectedUser['id'],
                ':user_a2' => $userId
            ]);

            $messages = $messageStmt->fetchAll();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ConnectID Messages</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #000;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        nav {
            padding: 20px;
            border-bottom: 1px solid #222;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        nav a {
            color: #fff;
            text-decoration: none;
        }

        nav a:hover {
            color: #ff6a00;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: #0b0b0b;
            border: 1px solid #222;
            border-radius: 16px;
            padding: 25px;
        }

        h1 {
            margin-top: 0;
        }

        .username {
            color: #888;
        }

        .conversation {
            margin-top: 30px;
        }

        .message {
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 12px;
            max-width: 75%;
            background: #151515;
        }

        .mine {
            margin-left: auto;
            border: 1px solid #ff6a00;
        }

        .theirs {
            margin-right: auto;
        }

        .time {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            margin-top: 20px;
            padding: 15px;
            background: #111;
            color: #fff;
            border: 1px solid #333;
            border-radius: 10px;
            resize: vertical;
        }

        button {
            margin-top: 10px;
            padding: 12px 22px;
            background: #ff6a00;
            color: #000;
            border: 0;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .empty {
            color: #777;
            margin-top: 20px;
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
    <a href="backend/logout.php">Logout</a>
</nav>

<div class="container">

    <div class="card">

        <?php if (!$selectedUser): ?>

            <h1>Messages</h1>

            <p class="empty">
                Select a connected Citizen to start a conversation.
            </p>

        <?php else: ?>

            <h1>
                <?= e($selectedUser['display_name']) ?>
            </h1>

            <div class="username">
                @<?= e($selectedUser['username']) ?>
            </div>

            <div class="conversation">

                <?php if (!$messages): ?>

                    <div class="empty">
                        No messages yet. Start the conversation.
                    </div>

                <?php else: ?>

                    <?php foreach ($messages as $message): ?>

                        <div class="message <?= ((int) $message['sender_id'] === $userId) ? 'mine' : 'theirs' ?>">

                            <?= nl2br(e($message['message'])) ?>

                            <div class="time">
                                <?= e($message['created_at']) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <form method="POST" action="backend/send_message.php">

                <input
                    type="hidden"
                    name="receiver_username"
                    value="<?= e($selectedUser['username']) ?>"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <textarea
                    name="message"
                    maxlength="5000"
                    placeholder="Write a message..."
                    required
                ></textarea>

                <button type="submit">
                    Send message
                </button>

            </form>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
