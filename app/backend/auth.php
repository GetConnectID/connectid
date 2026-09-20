<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        citizen,
        role,
        reputation,
        avatar,
        wallet_address,
        status
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $userId
]);

$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['status'] !== 'active') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]
        );
    }

    session_destroy();

    header('Location: ../login.html?error=session');
    exit;
}

/*
 * Refresh important session data from the database.
 * This prevents stale role/reputation information
 * from remaining in the session indefinitely.
 */
$_SESSION['user_id'] = (int) $currentUser['id'];
$_SESSION['username'] = $currentUser['username'];
$_SESSION['display_name'] = $currentUser['display_name'];
$_SESSION['citizen'] = (int) $currentUser['citizen'];
$_SESSION['role'] = $currentUser['role'];
$_SESSION['reputation'] = (int) $currentUser['reputation'];
$_SESSION['avatar'] = $currentUser['avatar'];
$_SESSION['wallet_address'] = $currentUser['wallet_address'];
