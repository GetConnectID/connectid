<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

if ($token === '') {
    header('Location: ../forgot_password.php?error=invalid');
    exit;
}

if (
    strlen($password) < 8 ||
    $password !== $passwordConfirm
) {
    header(
        'Location: ../reset_password.php?token=' .
        urlencode($token) .
        '&error=password'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Hash token
|--------------------------------------------------------------------------
*/

$tokenHash = hash('sha256', $token);

/*
|--------------------------------------------------------------------------
| Find valid reset token
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        expires_at,
        used_at
    FROM password_resets
    WHERE token_hash = :token_hash
    LIMIT 1
");

$stmt->execute([
    ':token_hash' => $tokenHash
]);

$reset = $stmt->fetch();

if (
    !$reset ||
    $reset['used_at'] !== null ||
    strtotime($reset['expires_at']) < time()
) {
    header('Location: ../reset_password.php?error=invalid_token');
    exit;
}

/*
|--------------------------------------------------------------------------
| Update password
|--------------------------------------------------------------------------
*/

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$pdo->beginTransaction();

try {

    $update = $pdo->prepare("
        UPDATE users
        SET password_hash = :password_hash
        WHERE id = :user_id
    ");

    $update->execute([
        ':password_hash' => $passwordHash,
        ':user_id' => (int) $reset['user_id']
    ]);

    /*
     * Mark token as used.
     */

    $used = $pdo->prepare("
        UPDATE password_resets
        SET used_at = CURRENT_TIMESTAMP
        WHERE id = :reset_id
    ");

    $used->execute([
        ':reset_id' => (int) $reset['id']
    ]);

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../reset_password.php?error=invalid_token');
    exit;
}

/*
|--------------------------------------------------------------------------
| Force current session to end
|--------------------------------------------------------------------------
*/

$_SESSION = [];

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'] ?? '',
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../reset_password.php?success=1');
exit;
