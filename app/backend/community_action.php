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
$action = trim($_POST['action'] ?? '');
$communityId = (int) ($_POST['community_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!in_array($action, ['create', 'join', 'leave'], true)) {
    header('Location: ../communities.php?error=invalid');
    exit;
}

if ($action === 'create') {

    if ($name === '') {
        header('Location: ../communities.php?error=name');
        exit;
    }

    if (mb_strlen($name) > 100) {
        header('Location: ../communities.php?error=name');
        exit;
    }

    if (mb_strlen($description) > 1000) {
        header('Location: ../communities.php?error=description');
        exit;
    }

    $pdo->beginTransaction();

    try {

        $insert = $pdo->prepare("
            INSERT INTO communities
            (
                name,
                description,
                creator_id,
                status
            )
            VALUES
            (
                :name,
                :description,
                :creator_id,
                'active'
            )
        ");

        $insert->execute([
            ':name' => $name,
            ':description' => $description,
            ':creator_id' => $userId
        ]);

        $newCommunityId = (int) $pdo->lastInsertId();

        $member = $pdo->prepare("
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
                'owner'
            )
        ");

        $member->execute([
            ':community_id' => $newCommunityId,
            ':user_id' => $userId
        ]);

        $pdo->commit();

        header('Location: ../communities.php?success=created');
        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        header('Location: ../communities.php?error=create');
        exit;
    }
}

if ($communityId <= 0) {
    header('Location: ../communities.php?error=invalid');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        creator_id,
        status
    FROM communities
    WHERE id = :community_id
    LIMIT 1
");

$stmt->execute([
    ':community_id' => $communityId
]);

$community = $stmt->fetch();

if (!$community || $community['status'] !== 'active') {
    header('Location: ../communities.php?error=community');
    exit;
}

$creatorId = (int) $community['creator_id'];

if ($action === 'join') {

    if ($creatorId === $userId) {
        header('Location: ../communities.php?error=owner');
        exit;
    }

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

    if ($existing->fetch()) {
        header('Location: ../communities.php?error=member');
        exit;
    }

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

    header('Location: ../communities.php?success=joined');
    exit;
}

if ($action === 'leave') {

    if ($creatorId === $userId) {
        header('Location: ../communities.php?error=owner');
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

    header('Location: ../communities.php?success=left');
    exit;
}

header('Location: ../communities.php?error=invalid');
exit;
