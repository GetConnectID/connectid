<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.status,
        c.requester_id,
        c.receiver_id,
        u.username,
        u.display_name,
        u.role,
        u.reputation
    FROM connections c
    JOIN users u
        ON u.id = IF(c.requester_id = :user_id, c.receiver_id, c.requester_id)
    WHERE c.requester_id = :requester
       OR c.receiver_id = :receiver
    ORDER BY c.created_at DESC
");

$stmt->execute([
    ':user_id' => $userId,
    ':requester' => $userId,
    ':receiver' => $userId
]);

$connections = $stmt->fetchAll();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectID Connections</title>

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
            margin: 50px auto;
            padding: 20px;
        }

        .card {
            background: #0b0b0b;
            border: 1px solid #222;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
        }

        h1 {
            margin-top: 0;
        }

        input {
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            background: #111;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
        }

        button {
            padding: 12px 20px;
            border: 0;
            border-radius: 8px;
            background: #ff6a00;
            color: #000;
            font-weight: bold;
            cursor: pointer;
        }

        .connection {
            padding: 18px 0;
            border-bottom: 1px solid #222;
        }

        .username {
            color: #888;
        }

        .status {
            color: #ff6a00;
            font-size: 13px;
            margin-top: 5px;
        }

        .empty {
            color: #777;
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

        <h1>Connections</h1>

        <p>Connect with another Citizen using their @username.</p>

        <form method="POST" action="backend/connections.php">

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
                value="<?= e(csrfToken()) ?>"
            >

            <button type="submit">
                Connect
            </button>

        </form>

    </div>

    <div class="card">

        <h2>Your connections</h2>

        <?php if (!$connections): ?>

            <div class="empty">
                You do not have any connections yet.
            </div>

        <?php else: ?>

            <?php foreach ($connections as $connection): ?>

                <div class="connection">

                    <strong>
                        <?= e($connection['display_name']) ?>
                    </strong>

                    <div class="username">
                        @<?= e($connection['username']) ?>
                    </div>

                    <div class="status">
                        <?= e(strtoupper($connection['status'])) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
