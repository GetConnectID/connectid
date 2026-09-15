<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.description,
        c.creator_id,
        c.created_at,
        u.username AS creator_username,
        u.display_name AS creator_name,
        COUNT(cm.id) AS member_count
    FROM communities c
    JOIN users u
        ON u.id = c.creator_id
    LEFT JOIN community_members cm
        ON cm.community_id = c.id
    WHERE c.status = 'active'
    GROUP BY
        c.id,
        c.name,
        c.description,
        c.creator_id,
        c.created_at,
        u.username,
        u.display_name
    ORDER BY c.created_at DESC
");

$communities = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ConnectID Communities</title>

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
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: #0b0b0b;
            border: 1px solid #222;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        input,
        textarea {
            width: 100%;
            padding: 14px;
            margin: 8px 0;
            background: #111;
            color: #fff;
            border: 1px solid #333;
            border-radius: 8px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        button {
            padding: 11px 18px;
            background: #ff6a00;
            color: #000;
            border: 0;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .community {
            border-top: 1px solid #222;
            padding: 22px 0;
        }

        .community:first-child {
            border-top: 0;
        }

        .description {
            color: #bbb;
            margin: 10px 0;
        }

        .meta {
            color: #777;
            font-size: 13px;
            margin-bottom: 15px;
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

        <h1>Communities</h1>

        <p>
            Discover and build communities around shared interests.
        </p>

    </div>

    <div class="card">

        <h2>Create a community</h2>

        <form method="POST" action="backend/communities.php">

            <input
                type="text"
                name="name"
                maxlength="100"
                placeholder="Community name"
                required
            >

            <textarea
                name="description"
                maxlength="2000"
                placeholder="Describe your community"
            ></textarea>

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <button type="submit">
                Create community
            </button>

        </form>

    </div>

    <div class="card">

        <h2>Available communities</h2>

        <?php if (!$communities): ?>

            <p>
                No communities exist yet.
            </p>

        <?php else: ?>

            <?php foreach ($communities as $community): ?>

                <div class="community">

                    <h3>
                        <?= e($community['name']) ?>
                    </h3>

                    <div class="description">
                        <?= nl2br(e($community['description'] ?? '')) ?>
                    </div>

                    <div class="meta">

                        Created by
                        @<?= e($community['creator_username']) ?>

                        ·

                        <?= (int) $community['member_count'] ?>
                        member(s)

                    </div>

                    <?php if ((int) $community['creator_id'] !== $userId): ?>

                        <form
                            method="POST"
                            action="backend/community_action.php"
                        >

                            <input
                                type="hidden"
                                name="community_id"
                                value="<?= (int) $community['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="join"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <button type="submit">
                                Join community
                            </button>

                        </form>

                    <?php else: ?>

                        <div class="meta">
                            You are the owner of this community.
                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
