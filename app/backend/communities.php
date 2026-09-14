<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            exit('Community name is required.');
        }

        if (strlen($name) > 100) {
            exit('Community name is too long.');
        }

        if (strlen($description) > 2000) {
            exit('Community description is too long.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO communities
             (name, description, creator_id, status)
             VALUES
             (:name, :description, :creator_id, :status)'
        );

        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'creator_id' => $userId,
            'status' => 'active'
        ]);

        $communityId = $pdo->lastInsertId();

        $member = $pdo->prepare(
            'INSERT INTO community_members
             (community_id, user_id, role)
             VALUES
             (:community_id, :user_id, :role)'
        );

        $member->execute([
            'community_id' => $communityId,
            'user_id' => $userId,
            'role' => 'owner'
        ]);

        header('Location: ../communities.php?created=1');
        exit;
    }
}

header('Location: ../communities.php');
exit;
