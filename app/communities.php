<?php

require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->query(
    'SELECT
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
     WHERE c.status = "active"
     GROUP BY
        c.id,
        c.name,
        c.description,
        c.creator_id,
        c.created_at,
        u.username,
        u.display_name
     ORDER BY c.created_at DESC'
);

$communities = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Communities - ConnectID</title>

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
    max-width: 1100px;
    padding: 50px;
}

.header {
    margin-bottom: 30px;
}

.header h1 {
    margin: 0 0 8px;
    font-size: 34px;
}

.header p {
    margin: 0;
    color: #888888;
}

.create {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 30px;
}

.create h2 {
    margin-top: 0;
}

.create form {
    display: grid;
    gap: 12px;
}

input,
textarea {
    width: 100%;
    background: #080808;
    color: #ffffff;
    border: 1px solid #333333;
    border-radius: 9px;
    padding: 13px;
    font-family: inherit;
    resize: vertical;
}

textarea {
    min-height: 90px;
}

button {
    width: fit-content;
    border: 0;
    border-radius: 9px;
    padding: 12px 20px;
    background: #ff7a00;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.community {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 25px;
}

.community h2 {
    margin-top: 0;
    margin-bottom: 8px;
}

.description {
    color: #888888;
    line-height: 1.5;
    min-height: 45px;
}

.meta {
    color: #666666;
    font-size: 13px;
    margin: 18px 0;
}

.actions {
    display: flex;
    gap: 10px;
}

.actions form {
    margin: 0;
}

.secondary {
    background: #222222;
}

.empty {
    background: #111111;
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 30px;
    color: #777777;
}

@media (max-width: 850px) {

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

    .grid {
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

<a href="connections.php">Connections</a>

<a href="communities.php" class="active">Communities</a>

<a href="profile.php">Profile</a>

<a href="backend/logout.php">Sign out</a>

</nav>

</aside>

<main class="main">

<div class="header">

<h1>Communities</h1>

<p>
Discover communities and participate in shared interests.
</p>

</div>

<div class="create">

<h2>Create a community</h2>

<form action="backend/communities.php" method="POST">

<input
type="hidden"
name="action"
value="create"
>

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

<button type="submit">
Create community
</button>

</form>

</div>

<?php if (!$communities): ?>

<div class="empty">

No communities exist yet.

Be the first to create one.

</div>

<?php else: ?>

<div class="grid">

<?php foreach ($communities as $community): ?>

<div class="community">

<h2>
<?= htmlspecialchars($community['name']) ?>
</h2>

<div class="description">

<?= nl2br(htmlspecialchars($community['description'])) ?>

</div>

<div class="meta">

<?= (int) $community['member_count'] ?> member(s)

<br>

Created by @<?= htmlspecialchars($community['creator_username']) ?>

</div>

<div class="actions">

<form
action="backend/community_action.php"
method="POST"
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

<button type="submit">
Join
</button>

</form>

<form
action="backend/community_action.php"
method="POST"
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

<button
type="submit"
class="secondary"
>
Leave
</button>

</form>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</main>

</div>

</body>

</html>
