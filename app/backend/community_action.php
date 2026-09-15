<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../communities.php');
    exit;
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];

$communityId = (int) ($_POST['community_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($communityId <= 0) {
    header('Location: ../communities.php?error=invalid_community');
    exit;
}

if (!in_array($action, ['join', 'leave'], true)) {
    header('Location: ../communities.php?error=invalid_action');
    exit;
}

$communityStmt = $pdo->prepare("
    SELECT id, creator_id
    FROM communities
    WHERE id = :id
      AND status = 'active'
    LIMIT 1
");

$communityStmt->execute([
    ':id' => $communityId
]);

$community = $communityStmt->fetch();

if (!$community) {
    header('Location: ../communities.php?error=community_not_found');
    exit;
}

if ($action === 'join') {

    $existing = $pdo->prepare("
        SELECT id
        FROM community_members
        WHERE community_id = :community_id
          AND user_id = :user_id
        LIMIT 1
    ");

    $existing->execute([
        ':community_id' => $communityId,
        ':user_id' => $userId
    ]);

    if (!$existing->fetch()) {

        $insert = $pdo->prepare("
            INSERT INTO community_members
            (
                community_id,
                user_id,
                role
            )
            VALUES
            (
                :community_id,
                :user_id,
                'member'
            )
        ");

        $insert->execute([
            ':community_id' => $communityId,
            ':user_id' => $userId
        ]);
    }

} elseif ($action === 'leave') {

    if ((int) $community['creator_id'] === $userId) {
        header('Location: ../communities.php?error=owner_cannot_leave');
        exit;
    }

    $delete = $pdo->prepare("
        DELETE FROM community_members
        WHERE community_id = :community_id
          AND user_id = :user_id
    ");

    $delete->execute([
        ':community_id' => $communityId,
        ':user_id' => $userId
    ]);
}

header('Location: ../communities.php');
exit;
