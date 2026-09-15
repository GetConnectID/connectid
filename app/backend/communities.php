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

if ($action !== 'create') {
    header('Location: ../communities.php?error=invalid_action');
    exit;
}

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
