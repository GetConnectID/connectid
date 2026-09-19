<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$currentUserId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.status,
        c.created_at,
        u.username,
        u.display_name,
        u.avatar
    FROM connections c
    JOIN users u ON u.id = c.requester_id
    WHERE c.receiver_id = :user_id
    AND c.status = 'pending'
    ORDER BY c.created_at DESC
");

$stmt->execute([
    ':user_id' => $currentUserId
]);

$incomingRequests = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        u.username,
        u.display_name,
        u.avatar
    FROM connections c
    JOIN users u
        ON u.id = CASE
            WHEN c.requester_id = :user_id THEN c.receiver_id
            ELSE c.requester_id
        END
    WHERE (c.requester_id = :user_id OR c.receiver_id = :user_id)
    AND c.status = 'accepted'
    ORDER BY u.display_name ASC
");

$stmt->execute([
    ':user_id' => $currentUserId
]);

$connections = $stmt->fetchAll();

$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connections | ConnectID</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0b0b0b;
            color: #ffffff;
        }

        nav {
            padding: 18px 24px;
            border-bottom: 1px solid #222;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            margin-right: 20px;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #0d0d0d;
            color: white;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .primary {
            background: #ff7a00;
            color: white;
        }

        .accept {
            background: #ff7a00;
            color: white;
            margin-right: 8px;
        }

        .decline {
            background: #333;
            color: white;
        }

        .person {
            padding: 14px 0;
            border-bottom: 1px solid #292929;
        }

        .person:last-child {
            border-bottom: 0;
        }

        .username {
            color: #aaa;
            margin-top: 4px;
        }

        .empty {
            color: #888;
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

    <h1>Connections</h1>

    <div class="card">
        <h2>Connect with a Citizen</h2>

        <form method="post" action="backend/connections.php">
            <input
                type="text"
                name="username"
                placeholder="@username"
                maxlength="30"
                required
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <button class="primary" type="submit">
                Connect
            </button>
        </form>
    </div>

    <div class="card">
        <h2>Incoming Requests</h2>

        <?php if (!$incomingRequests): ?>

            <p class="empty">No pending connection requests.</p>

        <?php else: ?>

            <?php foreach ($incomingRequests as $request): ?>

                <div class="person">

                    <strong>
                        <?= htmlspecialchars($request['display_name']) ?>
                    </strong>

                    <div class="username">
                        @<?= htmlspecialchars($request['username']) ?>
                    </div>

                    <br>

                    <form
                        method="post"
                        action="backend/connection_action.php"
                        style="display:inline;"
                    >
                        <input
                            type="hidden"
                            name="connection_id"
                            value="<?= (int) $request['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="accept"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfToken) ?>"
                        >

                        <button class="accept" type="submit">
                            Accept
                        </button>
                    </form>

                    <form
                        method="post"
                        action="backend/connection_action.php"
                        style="display:inline;"
                    >
                        <input
                            type="hidden"
                            name="connection_id"
                            value="<?= (int) $request['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="decline"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfToken) ?>"
                        >

                        <button class="decline" type="submit">
                            Decline
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Your Connections</h2>

        <?php if (!$connections): ?>

            <p class="empty">
                You don't have any connections yet.
            </p>

        <?php else: ?>

            <?php foreach ($connections as $connection): ?>

                <div class="person">

                    <strong>
                        <?= htmlspecialchars($connection['display_name']) ?>
                    </strong>

                    <div class="username">
                        @<?= htmlspecialchars($connection['username']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
