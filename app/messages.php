<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];
$csrfToken = csrfToken();

$targetUsername = trim($_GET['user'] ?? '');
$targetUsername = ltrim($targetUsername, '@');

$targetUser = null;
$messages = [];
$connections = [];
$connectionAccepted = false;

/*
|--------------------------------------------------------------------------
| Selected user
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

        $targetUserId = (int) $targetUser['id'];

        /*
         * Check connection.
         */

        $connectionStmt = $pdo->prepare("
            SELECT id
            FROM connections
            WHERE status = 'accepted'
            AND
            (
                (
                    requester_id = :user_id
                    AND receiver_id = :target_id
                )
                OR
                (
                    requester_id = :target_id_2
                    AND receiver_id = :user_id_2
                )
            )
            LIMIT 1
        ");

        $connectionStmt->execute([
            ':user_id' => $userId,
            ':target_id' => $targetUserId,
            ':target_id_2' => $targetUserId,
            ':user_id_2' => $userId
        ]);

        $connectionAccepted =
            (bool) $connectionStmt->fetch();

        if ($connectionAccepted) {

            /*
             * Load complete conversation.
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
                        m.sender_id = :user_id
                        AND m.receiver_id = :target_id
                    )
                    OR
                    (
                        m.sender_id = :target_id_2
                        AND m.receiver_id = :user_id_2
                    )
                ORDER BY m.id ASC
            ");

            $messageStmt->execute([
                ':user_id' => $userId,
                ':target_id' => $targetUserId,
                ':target_id_2' => $targetUserId,
                ':user_id_2' => $userId
            ]);

            $messages =
                $messageStmt->fetchAll();

            /*
             * Opening the conversation marks incoming
             * messages from this person as read.
             */

            $markRead = $pdo->prepare("
                UPDATE messages
                SET read_at = CURRENT_TIMESTAMP
                WHERE receiver_id = :receiver_id
                AND sender_id = :sender_id
                AND read_at IS NULL
            ");

            $markRead->execute([
                ':receiver_id' => $userId,
                ':sender_id' => $targetUserId
            ]);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Connections + unread counts
|--------------------------------------------------------------------------
*/

$connectionsStmt = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.display_name,
        u.avatar,
        u.role,
        u.reputation,

        (
            SELECT COUNT(*)
            FROM messages unread_messages
            WHERE unread_messages.sender_id = u.id
            AND unread_messages.receiver_id = :unread_receiver_id
            AND unread_messages.read_at IS NULL
        ) AS unread_count

    FROM connections c

    INNER JOIN users u
        ON u.id = CASE
            WHEN c.requester_id = :connection_user_id
            THEN c.receiver_id
            ELSE c.requester_id
        END

    WHERE
        (
            c.requester_id = :connection_user_id_2
            OR c.receiver_id = :connection_user_id_3
        )
        AND c.status = 'accepted'
        AND u.status = 'active'

    ORDER BY
        unread_count DESC,
        u.display_name ASC
");

$connectionsStmt->execute([
    ':unread_receiver_id' => $userId,
    ':connection_user_id' => $userId,
    ':connection_user_id_2' => $userId,
    ':connection_user_id_3' => $userId
]);

$connections =
    $connectionsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Total unread
|--------------------------------------------------------------------------
*/

$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM messages
    WHERE receiver_id = :receiver_id
    AND read_at IS NULL
");

$unreadStmt->execute([
    ':receiver_id' => $userId
]);

$totalUnread =
    (int) $unreadStmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Messages | ConnectID</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background: #0b0b0b;
            color: #ffffff;
        }

        nav {
            padding: 18px 24px;
            border-bottom: 1px solid #222222;
            background: #0b0b0b;
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

        .nav-unread {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 6px;
            margin-left: 5px;
            border-radius: 999px;
            background: #ff7a00;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            vertical-align: middle;
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

        .contact-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .contact-name {
            font-weight: 600;
        }

        .contact-username {
            color: #888888;
            font-size: 13px;
            margin-top: 4px;
        }

        .unread-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #ff7a00;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
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

    <a href="home.php">
        Home
    </a>

    <a href="profile.php">
        Profile
    </a>

    <a href="connections.php">
        Connections
    </a>

    <a href="messages.php">
        Messages

        <?php if ($totalUnread > 0): ?>

            <span class="nav-unread">
                <?= $totalUnread > 99 ? '99+' : $totalUnread ?>
            </span>

        <?php endif; ?>

    </a>

    <a href="communities.php">
        Communities
    </a>

    <a href="backend/logout.php">
        Logout
    </a>

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

                <?php
                $unreadCount =
                    (int) $connection['unread_count'];
                ?>

                <a
                    href="messages.php?user=<?= urlencode($connection['username']) ?>"
                    class="contact <?= $targetUsername === $connection['username'] ? 'active' : '' ?>"
                    data-contact-username="<?= htmlspecialchars($connection['username'], ENT_QUOTES, 'UTF-8') ?>"
                >

                    <div class="contact-row">

                        <div>

                            <div class="contact-name">
                                <?= htmlspecialchars(
                                    $connection['display_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <div class="contact-username">
                                @<?= htmlspecialchars(
                                    $connection['username'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                        </div>

                        <?php if ($unreadCount > 0): ?>

                            <span class="unread-badge">
                                <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                            </span>

                        <?php endif; ?>

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
                    <?= htmlspecialchars(
                        $targetUser['display_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <div class="chat-username">
                    @<?= htmlspecialchars(
                        $targetUser['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            </div>

            <div
                class="messages"
                id="messages"
                data-target="<?= htmlspecialchars(
                    $targetUser['username'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

                <?php if (!$messages): ?>

                    <div class="empty" id="empty-message">
                        No messages yet. Start the conversation.
                    </div>

                <?php else: ?>

                    <?php foreach ($messages as $message): ?>

                        <div
                            class="message <?= (int) $message['sender_id'] === $userId ? 'mine' : 'theirs' ?>"
                            data-message-id="<?= (int) $message['id'] ?>"
                        >

                            <?= nl2br(
                                htmlspecialchars(
                                    $message['message'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>

                            <div class="message-time">
                                <?= htmlspecialchars(
                                    $message['created_at'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
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
                        value="<?= htmlspecialchars(
                            $targetUser['username'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
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

const messagesContainer =
    document.getElementById('messages');

const targetUsername =
    messagesContainer.dataset.target;

let lastMessageId = 0;

const existingMessages =
    messagesContainer.querySelectorAll(
        '[data-message-id]'
    );

if (existingMessages.length > 0) {

    lastMessageId =
        parseInt(
            existingMessages[
                existingMessages.length - 1
            ].dataset.messageId,
            10
        );
}

function removeEmptyMessage() {

    const emptyMessage =
        document.getElementById(
            'empty-message'
        );

    if (emptyMessage) {
        emptyMessage.remove();
    }
}

function appendMessage(message) {

    const messageId =
        parseInt(message.id, 10);

    if (!messageId) {
        return;
    }

    if (
        document.querySelector(
            '[data-message-id="' +
            messageId +
            '"]'
        )
    ) {
        return;
    }

    removeEmptyMessage();

    const element =
        document.createElement('div');

    element.className =
        'message ' +
        (
            parseInt(
                message.sender_id,
                10
            ) === <?= $userId ?>
                ? 'mine'
                : 'theirs'
        );

    element.dataset.messageId =
        String(messageId);

    const text =
        document.createElement('div');

    text.textContent =
        message.message || '';

    const time =
        document.createElement('div');

    time.className =
        'message-time';

    time.textContent =
        message.created_at || '';

    element.appendChild(text);
    element.appendChild(time);

    messagesContainer.appendChild(
        element
    );

    lastMessageId =
        Math.max(
            lastMessageId,
            messageId
        );
}

async function refreshMessages() {

    try {

        const response =
            await fetch(
                'backend/get_messages.php?user=' +
                encodeURIComponent(
                    targetUsername
                ) +
                '&after_id=' +
                encodeURIComponent(
                    lastMessageId
                ) +
                '&_=' +
                Date.now(),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept':
                            'application/json'
                    }
                }
            );

        if (!response.ok) {
            return;
        }

        const data =
            await response.json();

        if (
            !data ||
            data.success !== true ||
            !Array.isArray(data.messages)
        ) {
            return;
        }

        let added = false;

        data.messages.forEach(
            function (message) {

                const before =
                    lastMessageId;

                appendMessage(message);

                if (
                    lastMessageId >
                    before
                ) {
                    added = true;
                }
            }
        );

        if (added) {

            messagesContainer.scrollTop =
                messagesContainer.scrollHeight;
        }

        /*
         * Refresh the global unread badge too.
         */
        refreshGlobalMessageStatus();

    } catch (error) {

        /*
         * Ignore temporary polling failures.
         */
    }
}

async function refreshGlobalMessageStatus() {

    try {

        const response =
            await fetch(
                'backend/message_status.php?_=' +
                Date.now(),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept':
                            'application/json'
                    }
                }
            );

        if (!response.ok) {
            return;
        }

        const data =
            await response.json();

        if (
            !data ||
            data.success !== true
        ) {
            return;
        }

        /*
         * Update the navigation badge.
         */
        const links =
            Array.from(
                document.querySelectorAll('a')
            ).filter(function (link) {

                const href =
                    link.getAttribute(
                        'href'
                    ) || '';

                return (
                    href === 'messages.php' ||
                    href === './messages.php' ||
                    href.endsWith(
                        '/messages.php'
                    )
                );
            });

        links.forEach(
            function (link) {

                let badge =
                    link.querySelector(
                        '.connectid-message-badge'
                    );

                const total =
                    parseInt(
                        data.total_unread,
                        10
                    ) || 0;

                if (total > 0) {

                    if (!badge) {

                        badge =
                            document.createElement(
                                'span'
                            );

                        badge.className =
                            'connectid-message-badge';

                        link.appendChild(
                            badge
                        );
                    }

                    badge.textContent =
                        total > 99
                            ? '99+'
                            : String(total);

                    badge.classList.remove(
                        'hidden'
                    );

                } else if (badge) {

                    badge.textContent = '';

                    badge.classList.add(
                        'hidden'
                    );
                }
            }
        );

    } catch (error) {

        /*
         * Ignore temporary failures.
         */
    }
}

messagesContainer.scrollTop =
    messagesContainer.scrollHeight;

/*
 * Start immediately.
 */
refreshMessages();

/*
 * Check for new messages every 2 seconds.
 */
setInterval(
    refreshMessages,
    2000
);

</script>

<?php endif; ?>

</body>

</html>
