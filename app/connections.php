<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT
        c.id,
        c.status,
        c.requester_id,
        c.receiver_id,
        u.username,
        u.display_name,
        u.avatar,
        u.role,
        u.reputation
     FROM connections c
     JOIN users u
       ON (
            CASE
                WHEN c.requester_id = :user_id THEN u.id = c.receiver_id
                ELSE u.id = c.requester_id
            END
          )
     WHERE
        c.requester_id = :user_id_2
        OR c.receiver_id = :user_id_3
     ORDER BY c.created_at DESC'
);

$stmt->execute([
    'user_id' => $userId,
    'user_id_2' => $userId,
    'user_id_3' => $userId
]);

$connections = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Connections - ConnectID</title>

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

h1 {
    margin: 0 0 8px;
    font-size: 34px;
}

.subtitle {
    color: #888888;
    margin-bottom: 30px;
}

.search {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
}

.search form {
    display: flex;
    gap: 12px;
}

input {
    flex: 1;
    padding: 14px;
    background: #080808;
    border: 1px solid #333333;
    border-radius: 9px;
    color: #ffffff;
    font-size: 15px;
}

button {
    border: 0;
    border-radius: 9px;
    padding: 14px 20px;
    background: #ff7a00;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

.connection {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.person {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #222222;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #777777;
    font-size: 11px;
}

.name {
    font-weight: 700;
    margin-bottom: 5px;
}

.username {
    color: #888888;
    font-size: 14px;
}

.status {
    color: #ff7a00;
    font-size: 12px;
    font-weight: 700;
}

.empty {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 35px;
    color: #777777;
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

    .search form {
        flex-direction: column;
    }

    .connection {
        align-items: flex-start;
        gap: 15px;
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

<a href="messages.html">Messages</a>

<a href="connections.php" class="active">Connections</a>

<a href="communities.html">Communities</a>

<a href="profile.php">Profile</a>

<a href="backend/logout.php">Sign out</a>

</nav>

</aside>

<main class="main">

<h1>Connections</h1>

<div class="subtitle">
Build trusted connections inside ConnectID.
</div>

<div class="search">

<form action="backend/connections.php" method="POST">

<input
type="text"
name="username"
placeholder="@username"
required
>

<input
type="hidden"
name="action"
value="connect"
>

<button type="submit">
Connect
</button>

</form>

</div>

<?php if (isset($_GET['sent'])): ?>

<div class="connection">
<div class="status">
Connection request sent.
</div>
</div>

<?php endif; ?>

<?php if (!$connections): ?>

<div class="empty">

You don't have any connections yet.

</div>

<?php else: ?>

<?php foreach ($connections as $connection): ?>

<div class="connection">

<div class="person">

<div class="avatar">
Avatar
</div>

<div>

<div class="name">
<?= htmlspecialchars($connection['display_name']) ?>
</div>

<div class="username">
@<?= htmlspecialchars($connection['username']) ?>
</div>

</div>

</div>

<div class="status">

<?= htmlspecialchars(strtoupper($connection['status'])) ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</main>

</div>

</body>

</html>
