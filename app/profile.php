<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$stmt = $pdo->prepare(
    'SELECT id, username, display_name, avatar, citizen, role, reputation, wallet_address, created_at
     FROM users
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $_SESSION['user_id']
]);

$user = $stmt->fetch();

if (!$user) {
    header('Location: backend/logout.php');
    exit;
}

$role = strtoupper($user['role']);
$walletStatus = $user['wallet_address']
    ? 'Connected'
    : 'Not connected';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Your Identity - ConnectID</title>

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
            max-width: 1000px;
            padding: 50px;
        }

        .title {
            margin-bottom: 30px;
        }

        .title h1 {
            margin: 0 0 8px;
            font-size: 34px;
        }

        .title p {
            color: #888888;
        }

        .profile {
            background: #111111;
            border: 1px solid #292929;
            border-radius: 18px;
            padding: 35px;
        }

        .profile-top {
            display: flex;
            align-items: center;
            gap: 22px;
            padding-bottom: 30px;
            border-bottom: 1px solid #292929;
        }

        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #222222;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777777;
        }

        h2 {
            margin: 0 0 7px;
            font-size: 27px;
        }

        .username {
            color: #888888;
        }

        .role {
            display: inline-block;
            margin-top: 10px;
            color: #ff7a00;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 30px;
        }

        .detail {
            background: #0a0a0a;
            border: 1px solid #222222;
            border-radius: 12px;
            padding: 20px;
        }

        .label {
            color: #777777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .value {
            font-size: 18px;
            font-weight: 600;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            color: #ff7a00;
            text-decoration: none;
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
                padding: 25px 18px;
            }

            .details {
                grid-template-columns: 1fr;
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
            <a href="messages.php">Messages</a>
            <a href="connections.html">Connections</a>
            <a href="communities.html">Communities</a>
            <a href="profile.php" class="active">Profile</a>
            <a href="backend/logout.php">Sign out</a>
        </nav>

    </aside>

    <main class="main">

        <div class="title">
            <h1>Your Identity</h1>
            <p>This is your identity inside ConnectID.</p>
        </div>

        <section class="profile">

            <div class="profile-top">

                <div class="avatar">
                    Avatar
                </div>

                <div>

                    <h2>
                        <?= htmlspecialchars($user['display_name']) ?>
                    </h2>

                    <div class="username">
                        @<?= htmlspecialchars($user['username']) ?>
                    </div>

                    <div class="role">
                        <?= htmlspecialchars($role) ?>
                    </div>

                </div>

            </div>

            <div class="details">

                <div class="detail">
                    <div class="label">Identity</div>
                    <div class="value">Citizen</div>
                </div>

                <div class="detail">
                    <div class="label">Role</div>
                    <div class="value">
                        <?= htmlspecialchars($role) ?>
                    </div>
                </div>

                <div class="detail">
                    <div class="label">Reputation</div>
                    <div class="value">
                        <?= (int) $user['reputation'] ?>
                    </div>
                </div>

                <div class="detail">
                    <div class="label">Wallet</div>
                    <div class="value">
                        <?= htmlspecialchars($walletStatus) ?>
                    </div>
                </div>

            </div>

            <a href="home.php" class="back">
                ← Back to Home
            </a>

        </section>

    </main>

</div>

</body>
</html>
