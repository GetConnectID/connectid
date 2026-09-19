<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/csrf.php';

$userId = (int) $_SESSION['user_id'];
$csrfToken = csrfToken();

/*
|--------------------------------------------------------------------------
| Current user
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        role,
        reputation
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$userStmt->execute([
    ':user_id' => $userId
]);

$currentUser = $userStmt->fetch();

/*
|--------------------------------------------------------------------------
| Communities
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.name,
        c.description,
        c.creator_id,
        c.status,
        c.created_at,
        creator.username AS creator_username,
        creator.display_name AS creator_display_name,
        COUNT(cm.user_id) AS member_count,
        CASE
            WHEN c.creator_id = :user_id THEN 1
            WHEN EXISTS (
                SELECT 1
                FROM community_members cm2
                WHERE cm2.community_id = c.id
                AND cm2.user_id = :user_id
            ) THEN 1
            ELSE 0
        END AS is_member,
        CASE
            WHEN c.creator_id = :user_id THEN 1
            ELSE 0
        END AS is_creator
    FROM communities c
    JOIN users creator
        ON creator.id = c.creator_id
    LEFT JOIN community_members cm
        ON cm.community_id = c.id
    WHERE c.status = 'active'
    GROUP BY
        c.id,
        c.name,
        c.description,
        c.creator_id,
        c.status,
        c.created_at,
        creator.username,
        creator.display_name
    ORDER BY c.created_at DESC
");

$stmt->execute([
    ':user_id' => $userId
]);

$communities = $stmt->fetchAll();

$created = isset($_GET['created']);
$joined = isset($_GET['joined']);
$left = isset($_GET['left']);
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communities | ConnectID</title>

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
            background: #0b0b0b;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }

        nav a:hover {
            color: #ff7a00;
        }

        .container {
            max-width: 950px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #999999;
            margin-bottom: 30px;
        }

        .card {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .card h2 {
            margin-top: 0;
        }

        input,
        textarea {
            width: 100%;
            padding: 13px;
            margin-bottom: 12px;
            border: 1px solid #333333;
            border-radius: 8px;
            background: #0d0d0d;
            color: #ffffff;
            font-family: inherit;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        button {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .primary {
            background: #ff7a00;
            color: #ffffff;
        }

        .primary:hover {
            background: #e86d00;
        }

        .secondary {
            background: #333333;
            color: #ffffff;
        }

        .secondary:hover {
            background: #444444;
        }

        .community {
            padding: 20px 0;
            border-bottom: 1px solid #292929;
        }

        .community:last-child {
            border-bottom: none;
        }

        .community-name {
            font-size: 21px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .community-description {
            color: #cccccc;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .community-meta {
            color: #888888;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: #252525;
            color: #bbbbbb;
            font-size: 12px;
            margin-left: 8px;
        }

        .status-owner {
            color: #ff7a00;
        }

        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #191919;
            border: 1px solid #292929;
        }

        .message.success {
            border-color: #3b3b3b;
        }

        .message.error {
            border-color: #663333;
            color: #ffaaaa;
        }

        .empty {
            color: #888888;
        }

        @media (max-width: 700px) {
            nav {
                line-height: 2;
            }

            nav a {
                margin-right: 12px;
            }

            .container {
                margin-top: 25px;
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

<div class="container">

    <h1>Communities</h1>

    <div class="subtitle">
        Connect with Citizens around shared interests and ideas.
    </div>

    <?php if ($created): ?>

        <div class="message success">
            Community created successfully.
        </div>

    <?php endif; ?>

    <?php if ($joined): ?>

        <div class="message success">
            You joined the community.
        </div>

    <?php endif; ?>

    <?php if ($left): ?>

        <div class="message success">
            You left the community.
        </div>

    <?php endif; ?>

    <?php if ($error === 'invalid_name'): ?>

        <div class="message error">
            Please enter a valid community name.
        </div>

    <?php elseif ($error === 'invalid_description'): ?>

        <div class="message error">
            The community description is too long.
        </div>

    <?php elseif ($error === 'already_member'): ?>

        <div class="message error">
            You are already a member of this community.
        </div>

    <?php elseif ($error === 'already_owner'): ?>

        <div class="message error">
            You are already the owner of this community.
        </div>

    <?php elseif ($error === 'owner_cannot_leave'): ?>

        <div class="message error">
            The community owner cannot leave their own community.
        </div>

    <?php elseif ($error === 'community_not_found'): ?>

        <div class="message error">
            This community is no longer available.
        </div>

    <?php endif; ?>

    <div class="card">

        <h2>Create a Community</h2>

        <form method="post" action="backend/community_action.php">

            <input
                type="text"
                name="name"
                placeholder="Community name"
                maxlength="100"
                required
            >

            <textarea
                name="description"
                placeholder="What is this community about?"
                maxlength="2000"
            ></textarea>

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <button
                type="submit"
                class="primary"
            >
                Create Community
            </button>

        </form>

    </div>

    <div class="card">

        <h2>Explore Communities</h2>

        <?php if (!$communities): ?>

            <p class="empty">
                No communities have been created yet.
            </p>

        <?php else: ?>

            <?php foreach ($communities as $community): ?>

                <div class="community">

                    <div class="community-name">

                        <?= htmlspecialchars($community['name']) ?>

                        <?php if ((int) $community['is_creator'] === 1): ?>

                            <span class="status status-owner">
                                Owner
                            </span>

                        <?php elseif ((int) $community['is_member'] === 1): ?>

                            <span class="status">
                                Member
                            </span>

                        <?php endif; ?>

                    </div>

                    <?php if ($community['description'] !== ''): ?>

                        <div class="community-description">
                            <?= nl2br(htmlspecialchars($community['description'])) ?>
                        </div>

                    <?php endif; ?>

                    <div class="community-meta">

                        Created by
                        @<?= htmlspecialchars($community['creator_username']) ?>

                        ·

                        <?= (int) $community['member_count'] ?>

                        <?= (int) $community['member_count'] === 1 ? 'member' : 'members' ?>

                    </div>

                    <?php if ((int) $community['is_creator'] === 1): ?>

                        <span class="status status-owner">
                            You own this community
                        </span>

                    <?php elseif ((int) $community['is_member'] === 1): ?>

                        <form
                            method="post"
                            action="backend/community_action.php"
                            style="display: inline;"
                        >

                            <input
                                type="hidden"
                                name="community_id"
                                value="<?= (int) $community['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="leave"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($csrfToken) ?>"
                            >

                            <button
                                type="submit"
                                class="secondary"
                            >
                                Leave
                            </button>

                        </form>

                    <?php else: ?>

                        <form
                            method="post"
                            action="backend/community_action.php"
                            style="display: inline;"
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
                                value="<?= htmlspecialchars($csrfToken) ?>"
                            >

                            <button
                                type="submit"
                                class="primary"
                            >
                                Join
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
