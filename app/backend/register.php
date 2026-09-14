<?php

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

if ($displayName === '' || $username === '' || $password === '') {
    exit('Please complete all required fields.');
}

if ($password !== $passwordConfirm) {
    exit('Passwords do not match.');
}

if (strlen($password) < 8) {
    exit('Password must contain at least 8 characters.');
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    exit('Username may only contain letters, numbers and underscores.');
}

try {
    $check = $pdo->prepare(
        'SELECT id FROM users WHERE username = :username LIMIT 1'
    );

    $check->execute([
        'username' => strtolower($username)
    ]);

    if ($check->fetch()) {
        exit('This username is already taken.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users
        (username, display_name, password_hash, citizen, role, reputation, status)
        VALUES
        (:username, :display_name, :password_hash, 1, :role, 0, :status)'
    );

    $stmt->execute([
        'username' => strtolower($username),
        'display_name' => $displayName,
        'password_hash' => $passwordHash,
        'role' => 'citizen',
        'status' => 'active'
    ]);

    header('Location: ../login.html?registered=1');
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    exit('Registration failed.');
}
