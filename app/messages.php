<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];
$csrfToken = csrfToken();

$targetUsername = trim($_GET['user'] ?? '');
$targetUser = null;
$messages = [];
$connectionAccepted = false;

/*
|--------------------------------------------------------------------------
| Load selected user
|--------------------------------------------------------------------------
*/

if ($targetUsername !== '') {

    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            display_name,
            avatar,
            role,
            reputation,
            citizen
        FROM users
        WHERE username = :username
        AND status = 'active'
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $targetUsername
    ]);

    $targetUser = $stmt->fetch();

    if ($targetUser) {

        /*
         * Check accepted connection.
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
            ':target_id' => (int) $targetUser['id']
        ]);

        $connectionAccepted = (bool) $connection->fetch();

        if ($connectionAccepted) {

            $messageStmt = $pdo->prepare("
                SELECT
                    m.id,
                    m.sender_id,
                    m.receiver_id,
                    m.message,
                    m.created_at,
                    u.username,
                    u.display_name
                FROM messages m
                JOIN users u
                    ON u.id = m.sender_id
                WHERE
                    (
                        m.sender_id = :user_id
                        AND m.receiver_id = :target_id
                    )
                    OR
                    (
                        m.sender_id = :target_id
                        AND m.receiver_id = :user_id
                    )
                ORDER BY m.created_at ASC, m.id ASC
            ");

            $messageStmt->execute([
                ':user_id' => $userId,
                ':target_id' => (int) $targetUser['id']
            ]);

            $messages = $messageStmt->fetchAll();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Connections for sidebar
|--------------------------------------------------------------------------
*/

$connectionsStmt = $pdo->prepare("
    SELECT
        u.username,
        u.display_name,
        u.avatar,
        u.role,
        u.reputation
    FROM connections c
    JOIN users u
        ON u.id = CASE
            WHEN c.requester_id = :user_id THEN c.receiver_id
            ELSE c.requester_id
        END
    WHERE
        (c.requester_id = :user_id OR c.receiver_id = :user_id)
        AND c.status = 'accepted'
        AND u.status = 'active'
    ORDER BY u.display_name ASC
");

$connectionsStmt->execute([
    ':user_id' => $userId
]);

$connections = $connectionsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Messages | ConnectID</title>

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

        .layout {
            max-width: 1100px;
            height: calc(100vh - 70px);
            margin: 0 auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            border-left: 1px solid #222222;
            border-right: 1px solid #222222;
        }

        .sidebar {
            border-right: 1px solid #222222;
            overflow-y: auto;
            background: #101010;
        }

        .sidebar-title {
            padding: 20px;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 1px solid #222222;
        }

        .contact {
            display: block;
            padding: 16px 20px;
            border-bottom: 1px solid #202020;
            color: #ffffff;
            text-decoration: none;
        }

        .contact:hover {
            background: #171717;
        }

        .contact.active {
            background: #1b1b1b;
            border-left: 3px solid #ff7a00;
        }

        .contact-name {
            font-weight: 600;
        }

        .contact-username {
            color: #888888;
            font-size: 13px;
            margin-top: 4px;
        }

        .chat {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .chat-header {
            padding: 18px 22px;
            border-bottom: 1px solid #222222;
            background: #101010;
        }

        .chat-name {
            font-weight: 700;
        }

        .chat-username {
            color: #888888;
            font-size: 13px;
            margin-top: 4px;
        }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 70%;
            padding: 11px 14px;
            border-radius: 12px;
            line-height: 1.45;
            word-wrap: break-word;
        }

        .message.mine {
            align-self: flex-end;
            background: #ff7a00;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .message.theirs {
            align-self: flex-start;
            background: #1c1c1c;
            color: #ffffff;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            margin-top: 5px;
            font-size: 10px;
            opacity: 0.65;
        }

        .composer {
            padding: 15px;
            border-top: 1px solid #222222;
            background: #101010;
        }

        .composer form {
            display: flex;
            gap: 10px;
        }

        .composer input[type="text"] {
            flex: 1;
            min-width: 0;
            padding: 13px;
            border: 1px solid #333333;
            border-radius: 10px;
            background: #0d0d0d;
            color: #ffffff;
        }

        .composer button {
            padding: 0 20px;
            border: none;
            border-radius: 10px;
            background: #ff7a00;
            color: #ffffff;
            cursor: pointer;
            font-weight: 600;
        }

        .empty {
            color: #888888;
            text-align: center;
            margin: auto;
            padding: 30px;
        }

        .notice {
            padding: 30px;
            color: #999999;
            text-align: center;
        }

        @media (max-width: 750px) {

            .layout {
                grid-template-columns: 1fr;
                height: auto;
                border: none;
            }

            .sidebar {
                max-height: 220px;
                border-right: none;
                border-bottom: 1px solid #222222;
            }

            .chat {
                min-height: 600px;
            }

            .message {
                max-width: 85%;
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
    <a href="backend/logout.php">Logout</a>
</nav>

<div class="layout">

    <aside class="sidebar">

        <div class="sidebar-title">
            Messages
        </div>

        <?php if (!$connections): ?>

            <div class="notice">
                No connections yet.
            </div>

        <?php else: ?>

            <?php foreach ($connections as $connection): ?>

                <a
                    href="messages.php?user=<?= urlencode($connection['username']) ?>"
                    class="contact <?= $targetUsername === $connection['username'] ? 'active' : '' ?>"
                >

                    <div class="contact-name">
                        <?= htmlspecialchars($connection['display_name']) ?>
                    </div>

                    <div class="contact-username">
                        @<?= htmlspecialchars($connection['username']) ?>
                    </div>

                </a>

            <?php endforeach; ?>

        <?php endif; ?>

    </aside>

    <main class="chat">

        <?php if (!$targetUser): ?>

            <div class="empty">
                Select a connection to start messaging.
            </div>

        <?php elseif (!$connectionAccepted): ?>

            <div class="empty">
                You can only message accepted connections.
            </div>

        <?php else: ?>

            <div class="chat-header">

                <div class="chat-name">
                    <?= htmlspecialchars($targetUser['display_name']) ?>
                </div>

                <div class="chat-username">
                    @<?= htmlspecialchars($targetUser['username']) ?>
                </div>

            </div>

            <div
                class="messages"
                id="messages"
                data-target="<?= htmlspecialchars($targetUser['username'], ENT_QUOTES) ?>"
            >

                <?php if (!$messages): ?>

                    <div class="empty">
                        No messages yet. Start the conversation.
                    </div>

                <?php else: ?>

                    <?php foreach ($messages as $message): ?>

                        <div
                            class="message <?= (int) $message['sender_id'] === $userId ? 'mine' : 'theirs' ?>"
                            data-message-id="<?= (int) $message['id'] ?>"
                        >

                            <?= nl2br(htmlspecialchars($message['message'])) ?>

                            <div class="message-time">
                                <?= htmlspecialchars($message['created_at']) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <div class="composer">

                <form
                    method="post"
                    action="backend/send_message.php"
                >

                    <input
                        type="text"
                        name="message"
                        maxlength="2000"
                        placeholder="Write a message..."
                        autocomplete="off"
                        required
                    >

                    <input
                        type="hidden"
                        name="receiver_username"
                        value="<?= htmlspecialchars($targetUser['username']) ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken) ?>"
                    >

                    <button type="submit">
                        Send
                    </button>

                </form>

            </div>

        <?php endif; ?>

    </main>

</div>

<?php if ($targetUser && $connectionAccepted): ?>

<script>
const messagesContainer = document.getElementById('messages');
const targetUsername = messagesContainer.dataset.target;

let lastMessageId = 0;

const existingMessages = messagesContainer.querySelectorAll('[data-message-id]');

if (existingMessages.length > 0) {
    lastMessageId = parseInt(
        existingMessages[existingMessages.length - 1].dataset.messageId,
        10
    );
}

async function refreshMessages() {

    try {

        const response = await fetch(
            'backend/get_messages.php?user=' +
            encodeURIComponent(targetUsername) +
            '&after_id=' +
            encodeURIComponent(lastMessageId),
            {
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );

        if (!response.ok) {
            return;
        }

        const data = await response.json();

        if (!Array.isArray(data.messages)) {
            return;
        }

        let added = false;

        data.messages.forEach(message => {

            if (
                document.querySelector(
                    '[data-message-id="' + message.id + '"]'
                )
            ) {
                return;
            }

            const element = document.createElement('div');

            element.className =
                'message ' +
                (
                    parseInt(message.sender_id, 10) === <?= $userId ?>
                        ? 'mine'
                        : 'theirs'
                );

            element.dataset.messageId = message.id;

            const text = document.createElement('div');
            text.innerHTML = message.message;

            const time = document.createElement('div');
            time.className = 'message-time';
            time.textContent = message.created_at;

            element.appendChild(text);
            element.appendChild(time);

            messagesContainer.appendChild(element);

            lastMessageId = Math.max(
                lastMessageId,
                parseInt(message.id, 10)
            );

            added = true;
        });

        if (added) {
            messagesContainer.scrollTop =
                messagesContainer.scrollHeight;
        }

    } catch (error) {
        /*
         * Ignore temporary polling failures.
         */
    }
}

messagesContainer.scrollTop =
    messagesContainer.scrollHeight;

setInterval(refreshMessages, 3000);
</script>

<?php endif; ?>

</body>
</html>
