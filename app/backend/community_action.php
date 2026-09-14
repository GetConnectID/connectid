<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];

$communityId = (int) ($_POST['community_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($communityId <= 0) {
    exit('Invalid community.');
}

if (!in_array($action, ['join', 'leave'], true)) {
    exit('Invalid action.');
}

$stmt = $pdo->prepare(
    'SELECT id, creator_id, status
     FROM communities
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $communityId
]);

$community = $stmt->fetch();

if (!$community || $community['status'] !== 'active') {
    exit('Community not found.');
}

if ($action === 'join') {

    $check = $pdo->prepare(
        'SELECT id
         FROM community_members
         WHERE community_id = :community_id
         AND user_id = :user_id
         LIMIT 1'
    );

    $check->execute([
        'community_id' => $communityId,
        'user_id' => $userId
    ]);

    if (!$check->fetch()) {

        $insert = $pdo->prepare(
            'INSERT INTO community_members
             (community_id, user_id, role)
             VALUES
             (:community_id, :user_id, :role)'
        );

        $insert->execute([
            'community_id' => $communityId,
            'user_id' => $userId,
            'role' => 'member'
        ]);
    }

} elseif ($action === 'leave') {

    if ((int) $community['creator_id'] !== (int) $userId) {

        $delete = $pdo->prepare(
            'DELETE FROM community_members
             WHERE community_id = :community_id
             AND user_id = :user_id'
        );

        $delete->execute([
            'community_id' => $communityId,
            'user_id' => $userId
        ]);
    }
}

header('Location: ../communities.php');
exit;
