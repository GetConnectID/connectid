<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        avatar,
        citizen,
        role,
        reputation,
        wallet_address,
        created_at
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.html');
    exit;
}

$eventsStmt = $pdo->prepare("
    SELECT
        points,
        type,
        reason,
        created_at
    FROM reputation_events
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 20
");

$eventsStmt->execute([':user_id' => $userId]);
$events = $eventsStmt->fetchAll();

$role = strtoupper($user['role']);
$citizen = $user['citizen'] ? 'CITIZEN' : 'NOT A CITIZEN';
$walletStatus = !empty($user['wallet_address'])
    ? 'CONNECTED'
    : 'NOT CONNECTED';

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
    <title>ConnectID Profile</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #000;
            color: #fff;
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

        .profile {
            border: 1px solid #222;
            border-radius: 16px;
            padding: 30px;
            background: #0b0b0b;
        }

        h1 {
            margin-top: 0;
        }

        .username {
            color: #aaa;
            margin-bottom: 30px;
        }

        .badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #ff6a00;
            color: #000;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .stat {
            background: #111;
            border: 1px solid #222;
            border-radius: 12px;
            padding: 18px;
        }

        .label {
            color: #888;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .value {
            font-size: 20px;
            font-weight: bold;
        }

        .reputation {
            margin-top: 30px;
        }

        .reputation h2 {
            margin-bottom: 15px;
        }

        .event {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #222;
        }

        .event-reason {
            color: #ddd;
        }

        .event-type {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .points {
            font-weight: bold;
            white-space: nowrap;
        }

        .positive {
            color: #7cff7c;
        }

        .negative {
            color: #ff6666;
        }

        .empty {
            color: #777;
            padding: 15px 0;
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

    <div class="profile">

        <span class="badge"><?= e($role) ?></span>

        <h1><?= e($user['display_name']) ?></h1>

        <div class="username">
            @<?= e($user['username']) ?>
        </div>

        <div class="stats">

            <div class="stat">
                <div class="label">Identity</div>
                <div class="value"><?= e($citizen) ?></div>
            </div>

            <div class="stat">
                <div class="label">Reputation</div>
                <div class="value"><?= (int) $user['reputation'] ?></div>
            </div>

            <div class="stat">
                <div class="label">Wallet</div>
                <div class="value"><?= e($walletStatus) ?></div>
            </div>

            <div class="stat">
                <div class="label">Role</div>
                <div class="value"><?= e($role) ?></div>
            </div>

        </div>

        <div class="reputation">

            <h2>Reputation history</h2>

            <?php if (!$events): ?>

                <div class="empty">
                    No reputation activity yet.
                </div>

            <?php else: ?>

                <?php foreach ($events as $event): ?>

                    <div class="event">

                        <div>
                            <div class="event-reason">
                                <?= e($event['reason']) ?>
                            </div>

                            <div class="event-type">
                                <?= e($event['type']) ?>
                            </div>
                        </div>

                        <div class="points <?= ((int) $event['points'] >= 0) ? 'positive' : 'negative' ?>">
                            <?= ((int) $event['points'] >= 0 ? '+' : '') . (int) $event['points'] ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>
