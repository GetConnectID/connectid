```php
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function sendResponse(int $status, array $data): void
{
    global $isAjax, $receiverUsername;

    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        echo json_encode($data);
        exit;
    }

    $url = '../messages.php';

    if (!empty($receiverUsername)) {
        $url .= '?user=' . urlencode($receiverUsername);

        if (!empty($data['error'])) {
            $url .= '&error=' . urlencode($data['error']);
        }
    }

    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../messages.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$receiverUsername = trim($_POST['receiver_username'] ?? '');
$message = trim($_POST['message'] ?? '');

$receiverUsername = ltrim($receiverUsername, '@');

if (
    $receiverUsername === '' ||
    strlen($receiverUsername) > 30 ||
    !preg_match('/^[A-Za-z0-9_]+$/', $receiverUsername)
) {
    sendResponse(400, [
        'success' => false,
        'error' => 'invalid_user'
    ]);
}

if ($message === '') {
    sendResponse(400, [
        'success' => false,
        'error' => 'empty_message'
    ]);
}

if (mb_strlen($message) > 2000) {
    sendResponse(400, [
        'success' => false,
        'error' => 'message_too_long'
    ]);
}

/*
|--------------------------------------------------------------------------|
| Find receiver
|--------------------------------------------------------------------------|
*/

$stmt = $pdo->prepare("
    SELECT id, username, status
    FROM users
    WHERE username = :receiver_username
    LIMIT 1
");

$stmt->execute([
    ':receiver_username' => $receiverUsername
]);

$receiver = $stmt->fetch();

if (!$receiver || $receiver['status'] !== 'active') {
    sendResponse(404, [
        'success' => false,
        'error' => 'user_not_found'
    ]);
}

$receiverId = (int) $receiver['id'];

if ($receiverId === $userId) {
    sendResponse(400, [
        'success' => false,
        'error' => 'self_message'
    ]);
}

/*
|--------------------------------------------------------------------------|
| Verify accepted connection
|--------------------------------------------------------------------------|
*/

$connectionStmt = $pdo->prepare("
    SELECT id
    FROM connections
    WHERE status = 'accepted'
    AND (
        (
            requester_id = :current_user_id
            AND receiver_id = :target_user_id
        )
        OR
        (
            requester_id = :target_user_id_reverse
            AND receiver_id = :current_user_reverse
        )
    )
    LIMIT 1
");

$connectionStmt->execute([
    ':current_user_id' => $userId,
    ':target_user_id' => $receiverId,
    ':target_user_id_reverse' => $receiverId,
    ':current_user_reverse' => $userId
]);

if (!$connectionStmt->fetch()) {
    sendResponse(403, [
        'success' => false,
        'error' => 'not_connected'
    ]);
}

/*
|--------------------------------------------------------------------------|
| Save message
|--------------------------------------------------------------------------|
*/

$insert = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, message)
    VALUES (:sender_id, :receiver_id, :message)
");

$insert->execute([
    ':sender_id' => $userId,
    ':receiver_id' => $receiverId,
    ':message' => $message
]);

sendResponse(200, [
    'success' => true
]);
```
