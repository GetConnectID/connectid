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

if ($username === '') {
    header('Location: ../forgot_password.php?error=missing');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE username = :username
    AND status = 'active'
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch();

/*
 * Always return the same response when the account
 * does not exist. This prevents username enumeration.
 */
if (!$user) {
    header('Location: ../forgot_password.php?sent=1');
    exit;
}

$userId = (int) $user['id'];

/*
 * Prevent repeated password-reset requests.
 * A new request is allowed after 5 minutes.
 */
$recentRequest = $pdo->prepare("
    SELECT id
    FROM password_resets
    WHERE user_id = :user_id
    AND created_at >= :cutoff
    LIMIT 1
");

$cutoff = date(
    'Y-m-d H:i:s',
    time() - (5 * 60)
);

$recentRequest->execute([
    ':user_id' => $userId,
    ':cutoff' => $cutoff
]);

if ($recentRequest->fetch()) {
    header('Location: ../forgot_password.php?sent=1');
    exit;
}

/*
 * Remove previous unused reset tokens for this user.
 * Only the newest reset request remains valid.
 */
$deleteOld = $pdo->prepare("
    DELETE FROM password_resets
    WHERE user_id = :user_id
    AND used_at IS NULL
");

$deleteOld->execute([
    ':user_id' => $userId
]);

/*
 * Remove expired or already-used tokens globally.
 */
$cleanup = $pdo->prepare("
    DELETE FROM password_resets
    WHERE used_at IS NOT NULL
    OR expires_at < NOW()
");

$cleanup->execute();

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);

$expiresAt = date(
    'Y-m-d H:i:s',
    time() + (30 * 60)
);

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
 * The token is intentionally not exposed here.
 * Email delivery will be connected in a later step.
 */

header('Location: ../forgot_password.php?sent=1');
exit;
