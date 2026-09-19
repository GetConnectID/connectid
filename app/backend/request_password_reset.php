<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$username = trim($_POST['username'] ?? '');
$username = ltrim($username, '@');

if (
    $username === '' ||
    strlen($username) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $username)
) {
    header('Location: ../forgot_password.php?error=invalid');
    exit;
}

/*
|--------------------------------------------------------------------------
| Find active user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        status
    FROM users
    WHERE username = :username
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Always return the same response
|--------------------------------------------------------------------------
|
| This prevents username enumeration.
|
*/

if (!$user || $user['status'] !== 'active') {
    header('Location: ../forgot_password.php?sent=1');
    exit;
}

$userId = (int) $user['id'];

/*
|--------------------------------------------------------------------------
| Remove older unused reset tokens
|--------------------------------------------------------------------------
*/

$cleanup = $pdo->prepare("
    DELETE FROM password_resets
    WHERE user_id = :user_id
    AND (
        used_at IS NOT NULL
        OR expires_at < CURRENT_TIMESTAMP
    )
");

$cleanup->execute([
    ':user_id' => $userId
]);

/*
|--------------------------------------------------------------------------
| Generate secure reset token
|--------------------------------------------------------------------------
*/

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);

$expiresAt = date(
    'Y-m-d H:i:s',
    time() + (60 * 30)
);

/*
|--------------------------------------------------------------------------
| Store token hash
|--------------------------------------------------------------------------
*/

$insert = $pdo->prepare("
    INSERT INTO password_resets
    (
        user_id,
        token_hash,
        expires_at
    )
    VALUES
    (
        :user_id,
        :token_hash,
        :expires_at
    )
");

$insert->execute([
    ':user_id' => $userId,
    ':token_hash' => $tokenHash,
    ':expires_at' => $expiresAt
]);

/*
|--------------------------------------------------------------------------
| Temporary development handling
|--------------------------------------------------------------------------
|
| Email delivery will be connected before production launch.
| The token itself is never stored in the database.
|
*/

header('Location: ../forgot_password.php?sent=1');
exit;
