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
$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| Create community
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        header('Location: ../communities.php?error=invalid_name');
        exit;
    }

    if (strlen($description) > 2000) {
        header('Location: ../communities.php?error=invalid_description');
        exit;
    }

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

    $communityId = (int) $pdo->lastInsertId();

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
        ':community_id' => $communityId,
        ':user_id' => $userId
    ]);

    header('Location: ../communities.php?created=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Join community
|--------------------------------------------------------------------------
*/

if ($action === 'join') {

    $communityId = (int) ($_POST['community_id'] ?? 0);

    if ($communityId <= 0) {
        header('Location: ../communities.php?error=invalid_community');
        exit;
    }

    $community = $pdo->prepare("
        SELECT
            id,
            creator_id,
            status
        FROM communities
        WHERE id = :community_id
        LIMIT 1
    ");

    $community->execute([
        ':community_id' => $communityId
    ]);

    $communityData = $community->fetch();

    if (!$communityData || $communityData['status'] !== 'active') {
        header('Location: ../communities.php?error=community_not_found');
        exit;
    }

    if ((int) $communityData['creator_id'] === $userId) {
        header('Location: ../communities.php?error=already_owner');
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
        header('Location: ../communities.php?error=already_member');
        exit;
    }

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
            'member'
        )
    ");

    $member->execute([
        ':community_id' => $communityId,
        ':user_id' => $userId
    ]);

    header('Location: ../communities.php?joined=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Leave community
|--------------------------------------------------------------------------
*/

if ($action === 'leave') {

    $communityId = (int) ($_POST['community_id'] ?? 0);

    if ($communityId <= 0) {
        header('Location: ../communities.php?error=invalid_community');
        exit;
    }

    $community = $pdo->prepare("
        SELECT
            id,
            creator_id,
            status
        FROM communities
        WHERE id = :community_id
        LIMIT 1
    ");

    $community->execute([
        ':community_id' => $communityId
    ]);

    $communityData = $community->fetch();

    if (!$communityData || $communityData['status'] !== 'active') {
        header('Location: ../communities.php?error=community_not_found');
        exit;
    }

    /*
     * The creator cannot leave their own community.
     */
    if ((int) $communityData['creator_id'] === $userId) {
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

    header('Location: ../communities.php?left=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Invalid action
|--------------------------------------------------------------------------
*/

header('Location: ../communities.php?error=invalid_action');
exit;
