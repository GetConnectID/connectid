<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $targetUsername = trim($_POST['username'] ?? '');
    $targetUsername = ltrim($targetUsername, '@');

    if ($targetUsername === '') {
        exit('Username is required.');
    }

    $stmt = $pdo->prepare(
        'SELECT id, username FROM users WHERE username = :username LIMIT 1'
    );

    $stmt->execute([
        'username' => strtolower($targetUsername)
    ]);

    $target = $stmt->fetch();

    if (!$target) {
        exit('User not found.');
    }

    if ((int) $target['id'] === (int) $userId) {
        exit('You cannot connect with yourself.');
    }

    if ($action === 'connect') {

        $check = $pdo->prepare(
            'SELECT id, status
             FROM connections
             WHERE
             (requester_id = :user_id AND receiver_id = :target_id)
             OR
             (requester_id = :target_id AND receiver_id = :user_id)
             LIMIT 1'
        );

        $check->execute([
            'user_id' => $userId,
            'target_id' => $target['id']
        ]);

        $existing = $check->fetch();

        if ($existing) {
            exit('A connection already exists or is pending.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO connections
            (requester_id, receiver_id, status)
            VALUES
            (:requester_id, :receiver_id, :status)'
        );

        $insert->execute([
            'requester_id' => $userId,
            'receiver_id' => $target['id'],
            'status' => 'pending'
        ]);

        header('Location: ../connections.php?sent=1');
        exit;
    }
}

header('Location: ../connections.php');
exit;
