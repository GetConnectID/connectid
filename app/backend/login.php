<?php

session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$username = ltrim($username, '@');

if ($username === '' || $password === '') {
    exit('Please enter your username and password.');
}

$stmt = $pdo->prepare(
    'SELECT id, username, display_name, password_hash, citizen, role, reputation, avatar, wallet_address, status
     FROM users
     WHERE username = :username
     LIMIT 1'
);

$stmt->execute([
    'username' => strtolower($username)
]);

$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    exit('Invalid username or password.');
}

if ($user['status'] !== 'active') {
    exit('This account is not active.');
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['display_name'] = $user['display_name'];
$_SESSION['citizen'] = (bool) $user['citizen'];
$_SESSION['role'] = $user['role'];
$_SESSION['reputation'] = $user['reputation'];
$_SESSION['avatar'] = $user['avatar'];
$_SESSION['wallet_address'] = $user['wallet_address'];

header('Location: home.php');
exit;
