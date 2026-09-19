<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: ../login.html?error=missing');
    exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (tooManyLoginAttempts($pdo, $username, $ipAddress)) {
    header('Location: ../login.html?error=ratelimit');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        password_hash,
        citizen,
        role,
        reputation,
        avatar,
        wallet_address,
        status
    FROM users
    WHERE username = :username
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch();

$successful = (
    $user &&
    password_verify($password, $user['password_hash']) &&
    $user['status'] === 'active'
);

$attempt = $pdo->prepare("
    INSERT INTO login_attempts
    (
        username,
        ip_address,
        successful
    )
    VALUES
    (
        :username,
        :ip_address,
        :successful
    )
");

$attempt->execute([
    ':username' => $username !== '' ? $username : null,
    ':ip_address' => $ipAddress,
    ':successful' => $successful ? 1 : 0
]);

if (!$user || !$successful) {
    header('Location: ../login.html?error=invalid');
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['display_name'] = $user['display_name'];
$_SESSION['citizen'] = (int) $user['citizen'];
$_SESSION['role'] = $user['role'];
$_SESSION['reputation'] = (int) $user['reputation'];
$_SESSION['avatar'] = $user['avatar'];
$_SESSION['wallet_address'] = $user['wallet_address'];

header('Location: ../home.php');
exit;
