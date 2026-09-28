<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

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
        avatar,
        citizen,
        role,
        reputation,
        wallet_address
    FROM users
    WHERE id = :current_user_id
    LIMIT 1
");

$userStmt->execute([
    ':current_user_id' => $userId
]);

$currentUser = $userStmt->fetch();

/*
|--------------------------------------------------------------------------
| Incoming connection requests
|--------------------------------------------------------------------------
*/

$incomingStmt = $pdo->prepare("
    SELECT
        c.id,
        c.created_at,
        u.id AS user_id,
        u.username,
        u.display_name,
        u.avatar,
        u.citizen,
        u.role,
        u.reputation,
        u.wallet_address
    FROM connections c
    INNER JOIN users u
        ON u.id = c.requester_id
    WHERE c.receiver_id = :incoming_user_id
      AND c.status = 'pending'
    ORDER BY c.created_at DESC
");

$incomingStmt->execute([
    ':incoming_user_id' => $userId
]);

$incomingRequests = $incomingStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Outgoing connection requests
|--------------------------------------------------------------------------
*/

$outgoingStmt = $pdo->prepare("
    SELECT
        c.id,
        c.created_at,
        u.id AS user_id,
        u.username,
        u.display_name,
        u.avatar,
        u.citizen,
        u.role,
        u.reputation,
        u.wallet_address
    FROM connections c
    INNER JOIN users u
        ON u.id = c.receiver_id
    WHERE c.requester_id = :outgoing_user_id
      AND c.status = 'pending'
    ORDER BY c.created_at DESC
");

$outgoingStmt->execute([
    ':outgoing_user_id' => $userId
]);

$outgoingRequests = $outgoingStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Accepted connections
|--------------------------------------------------------------------------
*/

$connectionsStmt = $pdo->prepare("
    SELECT
        c.id,
        c.created_at,
        u.id AS user_id,
        u.username,
        u.display_name,
        u.avatar,
        u.citizen,
        u.role,
        u.reputation,
        u.wallet_address
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
    ORDER BY u.display_name ASC, u.username ASC
");

$connectionsStmt->execute([
    ':connection_user_id' => $userId,
    ':connection_user_id_2' => $userId,
    ':connection_user_id_3' => $userId
]);

$connections = $connectionsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$errorMessages = [
    'invalid_username' => 'Please enter a valid username.',
    'user_not_found' => 'User not found.',
    'self' => 'You cannot connect with yourself.',
    'already_connected' => 'You are already connected with this user.',
    'already_pending' => 'A connection request is already pending.'
];

$successMessage = '';

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $successMessage = 'Connection request sent.';
}

$errorMessage = '';

if (
    isset($_GET['error']) &&
    isset($errorMessages[$_GET['error']])
) {
    $errorMessage = $errorMessages[$_GET['error']];
}

/*
|--------------------------------------------------------------------------
| Avatar helpers
|--------------------------------------------------------------------------
*/

function connectionInitials(
    string $displayName,
    string $username
): string {

    $name = trim($displayName);

    if ($name === '') {
        $name = $username;
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) >= 2) {
        return strtoupper(
            mb_substr($parts[0], 0, 1) .
            mb_substr($parts[1], 0, 1)
        );
    }

    return strtoupper(
        mb_substr($name, 0, 2)
    );
}

function renderConnectionAvatar(array $user): string
{
    if (!empty($user['avatar'])) {
        return '<img src="' .
            htmlspecialchars(
                $user['avatar'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '" alt="" class="connection-avatar-image">';
    }

    return '<div class="connection-avatar-placeholder">' .
        htmlspecialchars(
            connectionInitials(
                $user['display_name'] ?? '',
                $user['username'] ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Connections - ConnectID</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: #0b0b0b;
            color: #ffffff;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #111111;
            border-right: 1px solid #252525;
            padding: 28px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .brand {
            padding: 0 12px 30px;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .brand-name span {
            color: #ff7a00;
        }

        .brand-tagline {
            margin-top: 5px;
            color: #777777;
            font-size: 11px;
            line-height: 1.4;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav a {
            padding: 12px 13px;
            border-radius: 10px;
            color: #a5a5a5;
            font-size: 14px;
            transition: 0.15s ease;
        }

        .nav a:hover {
            background: #1b1b1b;
            color: #ffffff;
        }

        .nav a.active {
            background: #1b1b1b;
            color: #ffffff;
            border-left: 3px solid #ff7a00;
            padding-left: 10px;
        }

        .sidebar-bottom {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 24px;
        }

        .logout-link {
            display: block;
            padding: 11px 13px;
            color: #777777;
            font-size: 13px;
            border-radius: 10px;
        }

        .logout-link:hover {
            background: #1b1b1b;
            color: #ffffff;
        }

        .main {
            width: calc(100% - 240px);
            margin-left: 240px;
            padding: 42px;
        }

        .content {
            max-width: 1050px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-title {
            margin: 0;
            font-size: 32px;
            letter-spacing: -1px;
        }

        .page-subtitle {
            margin: 8px 0 0;
            color: #858585;
            font-size: 14px;
        }

        .notice {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .notice.success {
            background: rgba(255, 122, 0, 0.10);
            border: 1px solid rgba(255, 122, 0, 0.35);
            color: #ff9a45;
        }

        .notice.error {
            background: rgba(220, 60, 60, 0.10);
            border: 1px solid rgba(220, 60, 60, 0.30);
            color: #ff8585;
        }

        .card {
            background: #111111;
            border: 1px solid #242424;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .card-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }

        .card-description {
            margin: 5px 0 0;
            color: #777777;
            font-size: 13px;
        }

        .add-form {
            display: flex;
            gap: 10px;
        }

        .input {
            flex: 1;
            min-width: 0;
            height: 44px;
            border-radius: 9px;
            border: 1px solid #303030;
            background: #0b0b0b;
            color: #ffffff;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
        }

        .input:focus {
            border-color: #ff7a00;
        }

        .button {
            height: 44px;
            border: 0;
            border-radius: 9px;
            padding: 0 18px;
            background: #ff7a00;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }

        .button:hover {
            background: #e96d00;
        }

        .section-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .connection-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px;
            border-radius: 11px;
            background: #0c0c0c;
            border: 1px solid #202020;
        }

        .connection-avatar {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            border-radius: 50%;
            overflow: hidden;
            background: #1c1c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .connection-avatar-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .connection-avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff7a00;
            font-size: 13px;
            font-weight: 800;
        }

        .connection-info {
            min-width: 0;
            flex: 1;
        }

        .connection-name {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
        }

        .connection-username {
            margin-top: 3px;
            color: #777777;
            font-size: 12px;
        }

        .connection-meta {
            margin-top: 5px;
            color: #666666;
            font-size: 11px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 6px;
        }

        .badge-citizen {
            background: #1b1b1b;
            color: #a5a5a5;
        }

        .badge-role {
            background: rgba(255, 122, 0, 0.10);
            color: #ff9a45;
        }

        .request-actions {
            display: flex;
            gap: 7px;
        }

        .small-button {
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid #303030;
            background: #171717;
            color: #ffffff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .small-button:hover {
            border-color: #ff7a00;
        }

        .small-button.accept {
            background: #ff7a00;
            border-color: #ff7a00;
        }

        .empty {
            padding: 22px 10px;
            text-align: center;
            color: #666666;
            font-size: 13px;
        }

        .request-status {
            color: #777777;
            font-size: 12px;
            white-space: nowrap;
        }

        @media (max-width: 800px) {

            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid #252525;
                padding: 18px;
            }

            .app-shell {
                display: block;
            }

            .brand {
                padding-bottom: 16px;
            }

            .nav {
                flex-direction: row;
                overflow-x: auto;
            }

            .nav a {
                white-space: nowrap;
            }

            .sidebar-bottom {
                position: static;
                margin-top: 12px;
            }

            .main {
                width: 100%;
                margin-left: 0;
                padding: 24px 16px;
            }

            .page-title {
                font-size: 27px;
            }

            .add-form {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }

            .card-header {
                align-items: flex-start;
            }

            .connection-item {
                align-items: flex-start;
            }

            .request-actions {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="app-shell">

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-name">
                Connect<span>ID</span>
            </div>

            <div class="brand-tagline">
                Own your identity. Build your reputation.
            </div>

        </div>

        <nav class="nav">

            <a href="home.php">
                Home
            </a>

            <a href="profile.php">
                Profile
            </a>

            <a href="connections.php" class="active">
                Connections
            </a>

            <a href="messages.php">
                Messages
            </a>

            <a href="communities.php">
                Communities
            </a>

            <a href="notifications.php">
                Notifications
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-link"
            >
                Sign out
            </a>

        </div>

    </aside>

    <main class="main">

        <div class="content">

            <div class="page-header">

                <h1 class="page-title">
                    Connections
                </h1>

                <p class="page-subtitle">
                    Build your network through mutual connections.
                </p>

            </div>

            <?php if ($successMessage): ?>

                <div class="notice success">
                    <?= htmlspecialchars(
                        $successMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage): ?>

                <div class="notice error">
                    <?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2 class="card-title">
                            Add connection
                        </h2>

                        <p class="card-description">
                            Enter a ConnectID username to send a connection request.
                        </p>

                    </div>

                </div>

                <form
                    method="POST"
                    action="backend/connections.php"
                    class="add-form"
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

                    <input
                        type="text"
                        name="username"
                        class="input"
                        placeholder="@username"
                        maxlength="30"
                        autocomplete="off"
                        required
                    >

                    <button
                        type="submit"
                        class="button"
                    >
                        Connect
                    </button>

                </form>

            </section>

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2 class="card-title">
                            Connection requests
                        </h2>

                        <p class="card-description">
                            Requests waiting for your response.
                        </p>

                    </div>

                </div>

                <div class="section-list">

                    <?php if (!$incomingRequests): ?>

                        <div class="empty">
                            No incoming connection requests.
                        </div>

                    <?php else: ?>

                        <?php foreach ($incomingRequests as $request): ?>

                            <div class="connection-item">

                                <div class="connection-avatar">
                                    <?= renderConnectionAvatar($request) ?>
                                </div>

                                <div class="connection-info">

                                    <div class="connection-name">

                                        <?= htmlspecialchars(
                                            $request['display_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            (int) $request['citizen'] === 1
                                        ): ?>

                                            <span class="badge badge-citizen">
                                                Citizen
                                            </span>

                                        <?php endif; ?>

                                        <?php if (
                                            $request['role'] === 'creator'
                                        ): ?>

                                            <span class="badge badge-role">
                                                Creator
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="connection-username">

                                        @<?= htmlspecialchars(
                                            $request['username'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                    <div class="connection-meta">

                                        Reputation:
                                        <?= (int) $request['reputation'] ?>

                                    </div>

                                </div>

                                <div class="request-actions">

                                    <form
                                        method="POST"
                                        action="backend/connection_action.php"
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

                                        <button
                                            type="submit"
                                            class="small-button accept"
                                        >
                                            Accept
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        action="backend/connection_action.php"
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

                                        <button
                                            type="submit"
                                            class="small-button"
                                        >
                                            Decline
                                        </button>

                                    </form>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2 class="card-title">
                            My connections
                        </h2>

                        <p class="card-description">
                            People you are connected with.
                        </p>

                    </div>

                </div>

                <div class="section-list">

                    <?php if (!$connections): ?>

                        <div class="empty">
                            You do not have any connections yet.
                        </div>

                    <?php else: ?>

                        <?php foreach ($connections as $connection): ?>

                            <div class="connection-item">

                                <div class="connection-avatar">
                                    <?= renderConnectionAvatar($connection) ?>
                                </div>

                                <div class="connection-info">

                                    <div class="connection-name">

                                        <?= htmlspecialchars(
                                            $connection['display_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?php if (
                                            (int) $connection['citizen'] === 1
                                        ): ?>

                                            <span class="badge badge-citizen">
                                                Citizen
                                            </span>

                                        <?php endif; ?>

                                        <?php if (
                                            $connection['role'] === 'creator'
                                        ): ?>

                                            <span class="badge badge-role">
                                                Creator
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="connection-username">

                                        @<?= htmlspecialchars(
                                            $connection['username'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                    <div class="connection-meta">

                                        Reputation:
                                        <?= (int) $connection['reputation'] ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <section class="card">

                <div class="card-header">

                    <div>

                        <h2 class="card-title">
                            Sent requests
                        </h2>

                        <p class="card-description">
                            Connection requests you have sent.
                        </p>

                    </div>

                </div>

                <div class="section-list">

                    <?php if (!$outgoingRequests): ?>

                        <div class="empty">
                            No pending outgoing requests.
                        </div>

                    <?php else: ?>

                        <?php foreach ($outgoingRequests as $request): ?>

                            <div class="connection-item">

                                <div class="connection-avatar">
                                    <?= renderConnectionAvatar($request) ?>
                                </div>

                                <div class="connection-info">

                                    <div class="connection-name">

                                        <?= htmlspecialchars(
                                            $request['display_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                    <div class="connection-username">

                                        @<?= htmlspecialchars(
                                            $request['username'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                </div>

                                <div class="request-status">
                                    Pending
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>

</body>
</html>
