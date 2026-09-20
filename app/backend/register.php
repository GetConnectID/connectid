<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.html');
    exit;
}

$displayName = trim($_POST['display_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

$username = ltrim($username, '@');

if (
    $displayName === '' ||
    $username === '' ||
    $password === '' ||
    $passwordConfirm === ''
) {
    header('Location: ../register.html?error=missing');
    exit;
}

if (mb_strlen($displayName) > 100) {
    header('Location: ../register.html?error=display_name');
    exit;
}

if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
    header('Location: ../register.html?error=username');
    exit;
}

if (strlen($password) < 8) {
    header('Location: ../register.html?error=password');
    exit;
}

if ($password !== $passwordConfirm) {
    header('Location: ../register.html?error=match');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE username = :username
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

if ($stmt->fetch()) {
    header('Location: ../register.html?error=taken');
    exit;
}

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$insert = $pdo->prepare("
    INSERT INTO users
    (
        username,
        display_name,
        password_hash,
        citizen,
        role,
        reputation,
        status
    )
    VALUES
    (
        :username,
        :display_name,
        :password_hash,
        1,
        'citizen',
        0,
        'active'
    )
");

$insert->execute([
    ':username' => $username,
    ':display_name' => $displayName,
    ':password_hash' => $passwordHash
]);

header('Location: ../login.html?registered=1');
exit;
