<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$userId = $_SESSION['user_id'];

$username = trim($_GET['user'] ?? '');
$username = ltrim($username, '@');

$selectedUser = null;
$messages = [];

if ($username !== '') {

    $stmt = $pdo->prepare(
        'SELECT id, username, display_name, avatar, role, reputation
         FROM users
         WHERE username = :username
         LIMIT 1'
    );

    $stmt->execute([
        'username' => strtolower($username)
    ]);

    $selectedUser = $stmt->fetch();

    if ($selectedUser) {

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
            'other_user_id' => $selectedUser['id']
        ]);

        if ($connection->fetch()) {

            $stmt = $pdo->prepare(
                'SELECT
                    id,
                    sender_id,
                    receiver_id,
                    message,
                    created_at
                 FROM messages
                 WHERE
                    (sender_id = :user_id AND receiver_id = :other_user_id)
                    OR
                    (sender_id = :other_user_id AND receiver_id = :user_id)
                 ORDER BY created_at ASC'
            );

            $stmt->execute([
                'user_id' => $userId,
                'other_user_id' => $selectedUser['id']
            ]);

            $messages = $stmt->fetchAll();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Messages - ConnectID</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #050505;
    color: #ffffff;
    font-family: Arial, Helvetica, sans-serif;
}

.layout {
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 240px;
    background: #0d0d0d;
    border-right: 1px solid #222222;
    padding: 28px 18px;
}

.logo {
    font-size: 25px;
    font-weight: 700;
    padding: 0 12px;
    margin-bottom: 40px;
}

.logo span {
    color: #ff7a00;
}

.nav a {
    display: block;
    padding: 13px 12px;
    margin-bottom: 5px;
    border-radius: 9px;
    color: #999999;
    text-decoration: none;
}

.nav a:hover,
.nav a.active {
    background: #1a1a1a;
    color: #ffffff;
}

.main {
    flex: 1;
    display: flex;
    height: 100vh;
}

.conversations {
    width: 320px;
    border-right: 1px solid #222222;
    padding: 25px;
    overflow-y: auto;
}

.conversations h1 {
    font-size: 25px;
    margin-top: 0;
}

.search {
    width: 100%;
    padding: 12px;
    background: #111111;
    border: 1px solid #292929;
    border-radius: 9px;
    color: #ffffff;
}

.conversation {
    display: block;
    padding: 16px;
    margin-top: 12px;
    background: #111111;
    border: 1px solid #292929;
    border-radius: 12px;
    color: #ffffff;
    text-decoration: none;
}

.conversation:hover {
    border-color: #444444;
}

.username {
    color: #888888;
    font-size: 13px;
    margin-top: 4px;
}

.chat {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 24px;
    border-bottom: 1px solid #222222;
}

.chat-header h2 {
    margin: 0;
}

.chat-header span {
    color: #888888;
    font-size: 14px;
}

.messages {
    flex: 1;
    padding: 25px;
    overflow-y: auto;
}

.empty {
    color: #666666;
    text-align: center;
    margin-top: 80px;
}

.message {
    max-width: 65%;
    padding: 12px 15px;
    margin-bottom: 12px;
    border-radius: 14px;
    background: #151515;
}

.message.mine {
    margin-left: auto;
    background: #ff7a00;
}

.message-text {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.message-time {
    margin-top: 6px;
    font-size: 10px;
    opacity: 0.65;
}

.composer {
    padding: 20px;
    border-top: 1px solid #222222;
}

.composer form {
    display: flex;
    gap: 10px;
}

.composer input {
    flex: 1;
    padding: 14px;
    background: #111111;
    border: 1px solid #333333;
    border-radius: 10px;
    color: #ffffff;
}

.composer button {
    padding: 14px 22px;
    border: 0;
    border-radius: 10px;
    background: #ff7a00;
    color: #ffffff;
    font-weight: 700;
}

@media (max-width: 800px) {

    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        border-right: 0;
        border-bottom: 1px solid #222222;
    }

    .main {
        height: calc(100vh - 170px);
    }

    .conversations {
        display: none;
    }

    .message {
        max-width: 85%;
    }
}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

<div class="logo">
Connect<span>ID</span>
</div>

<nav class="nav">

<a href="home.php">Home</a>

<a href="messages.php" class="active">Messages</a>

<a href="connections.php">Connections</a>

<a href="communities.html">Communities</a>

<a href="profile.php">Profile</a>

<a href="backend/logout.php">Sign out</a>

</nav>

</aside>

<main class="main">

<section class="conversations">

<h1>Messages</h1>

<input
class="search"
placeholder="Search connections"
>

<?php if ($selectedUser): ?>

<a
class="conversation"
href="messages.php?user=<?= urlencode($selectedUser['username']) ?>"
>

<strong>
<?= htmlspecialchars($selectedUser['display_name']) ?>
</strong>

<div class="username">
@<?= htmlspecialchars($selectedUser['username']) ?>
</div>

</a>

<?php else: ?>

<div class="empty">
Select a connection to start messaging.
</div>

<?php endif; ?>

</section>

<section class="chat">

<?php if ($selectedUser): ?>

<header class="chat-header">

<h2>
<?= htmlspecialchars($selectedUser['display_name']) ?>
</h2>

<span>
@<?= htmlspecialchars($selectedUser['username']) ?>
</span>

</header>

<div class="messages">

<?php if (!$messages): ?>

<div class="empty">
No messages yet.
Start the conversation below.
</div>

<?php else: ?>

<?php foreach ($messages as $message): ?>

<div class="message <?= ((int) $message['sender_id'] === (int) $userId) ? 'mine' : '' ?>">

<div class="message-text">
<?= htmlspecialchars($message['message']) ?>
</div>

<div class="message-time">
<?= htmlspecialchars($message['created_at']) ?>
</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<div class="composer">

<form action="backend/send_message.php" method="POST">

<input
type="hidden"
name="username"
value="<?= htmlspecialchars($selectedUser['username']) ?>"
>

<input
type="text"
name="message"
placeholder="Write a message..."
maxlength="5000"
required
>

<button type="submit">
Send
</button>

</form>

</div>

<?php else: ?>

<div class="chat">

<div class="empty">
Your private messages will appear here.
</div>

</div>

<?php endif; ?>

</section>

</main>

</div>

</body>

</html>
