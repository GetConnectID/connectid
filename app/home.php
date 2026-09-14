<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$stmt = $pdo->prepare(
    'SELECT id, username, display_name, avatar, citizen, role, reputation, wallet_address
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
    ? 'Wallet connected'
    : 'No wallet connected';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Home - ConnectID</title>

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

        .creator {
            margin-top: 35px;
            padding: 12px;
            border: 1px solid #292929;
            border-radius: 10px;
            color: #ff7a00;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .main {
            flex: 1;
            padding: 50px;
            max-width: 1200px;
        }

        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            margin: 0 0 8px;
            font-size: 34px;
        }

        .welcome p {
            margin: 0;
            color: #888888;
        }

        .identity {
            background: #111111;
            border: 1px solid #292929;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 25px;
        }

        .identity-top {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #222222;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777777;
            font-size: 13px;
        }

        .identity h2 {
            margin: 0 0 5px;
        }

        .username {
            color: #888888;
        }

        .role {
            display: inline-block;
            margin-top: 8px;
            color: #ff7a00;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: #111111;
            border: 1px solid #292929;
            border-radius: 16px;
            padding: 24px;
        }

        .card-label {
            color: #777777;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .card-value {
            font-size: 27px;
            font-weight: 700;
        }

        .wallet {
            color: #999999;
        }

        .section {
            background: #111111;
            border: 1px solid #292929;
            border-radius: 16px;
            padding: 28px;
        }

        .section h2 {
            margin-top: 0;
        }

        .section p {
            color: #777777;
        }

        .button {
            display: inline-block;
            margin-top: 10px;
            padding: 11px 18px;
            border-radius: 9px;
            background: #ff7a00;
            color: #ffffff;
            text-decoration: none;
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
                padding: 25px 18px;
            }

            .stats {
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
            <a href="home.php" class="active">Home</a>
            <a href="messages.html">Messages</a>
            <a href="connections.html">Connections</a>
            <a href="communities.html">Communities</a>
            <a href="profile.php">Profile</a>
        </nav>

        <div class="creator">
            <?= htmlspecialchars($role) ?>
        </div>

        <div class="nav" style="margin-top:20px;">
            <a href="backend/logout.php">Sign out</a>
        </div>

    </aside>

    <main class="main">

        <section class="welcome">
            <h1>Welcome, <?= htmlspecialchars($user['display_name']) ?></h1>
            <p>Your ConnectID identity is active.</p>
        </section>

        <section class="identity">

            <div class="identity-top">

                <div class="avatar">
                    Avatar
                </div>

                <div>
                    <h2><?= htmlspecialchars($user['display_name']) ?></h2>

                    <div class="username">
                        @<?= htmlspecialchars($user['username']) ?>
                    </div>

                    <div class="role">
                        <?= htmlspecialchars($role) ?>
                    </div>
                </div>

            </div>

        </section>

        <section class="stats">

            <div class="card">
                <div class="card-label">Reputation</div>
                <div class="card-value">
                    <?= (int) $user['reputation'] ?>
                </div>
            </div>

            <div class="card">
                <div class="card-label">Identity</div>
                <div class="card-value">
                    Citizen
                </div>
            </div>

            <div class="card">
                <div class="card-label">Wallet</div>
                <div class="card-value wallet">
                    <?= htmlspecialchars($walletStatus) ?>
                </div>
            </div>

        </section>

        <section class="section">

            <h2>Connect with people</h2>

            <p>
                Build your network through trusted connections
                and become part of the ConnectID community.
            </p>

            <a href="connections.html" class="button">
                Find people
            </a>

        </section>

    </main>

</div>

</body>
</html>
