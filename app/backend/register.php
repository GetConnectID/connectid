<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.html');
    exit;
}

$displayName = trim($_POST['display_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

$username = ltrim($username, '@');

if (
    $displayName === '' ||
    $username === '' ||
    $password === '' ||
    $passwordConfirm === ''
) {
    header('Location: ../register.html?error=missing');
    exit;
}

if (mb_strlen($displayName) > 100) {
    header('Location: ../register.html?error=display_name');
    exit;
}

if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
    header('Location: ../register.html?error=username');
    exit;
}

if (strlen($password) < 8) {
    header('Location: ../register.html?error=password');
    exit;
}

if ($password !== $passwordConfirm) {
    header('Location: ../register.html?error=match');
    exit;
}

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {

    $pdo->beginTransaction();

    /*
     * The system_settings row acts as the permanent
     * Creator lock. The first registered user becomes
     * the Creator.
     */
    $creatorSetting = $pdo->prepare("
        SELECT setting_value
        FROM system_settings
        WHERE setting_key = 'creator_user_id'
        FOR UPDATE
    ");

    $creatorSetting->execute();

    $creatorUserId = $creatorSetting->fetchColumn();

    /*
     * If the setting does not exist yet, create it.
     * A value of 0 means that no Creator exists yet.
     */
    if ($creatorUserId === false) {

        $createSetting = $pdo->prepare("
            INSERT INTO system_settings
            (
                setting_key,
                setting_value
            )
            VALUES
            (
                'creator_user_id',
                '0'
            )
        ");

        $createSetting->execute();

        $creatorUserId = '0';
    }

    /*
     * Check username while the transaction is active.
     * The database unique index added later also protects
     * against duplicate usernames caused by simultaneous
     * registration attempts.
     */
    $usernameCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = :username
        LIMIT 1
    ");

    $usernameCheck->execute([
        ':username' => $username
    ]);

    if ($usernameCheck->fetch()) {
        $pdo->rollBack();

        header('Location: ../register.html?error=taken');
        exit;
    }

    $isFirstUser = (
        (string) $creatorUserId === '0'
    );

    $role = $isFirstUser
        ? 'creator'
        : 'citizen';

    $insert = $pdo->prepare("
        INSERT INTO users
        (
            username,
            display_name,
            password_hash,
            citizen,
            role,
            reputation,
            status
        )
        VALUES
        (
            :username,
            :display_name,
            :password_hash,
            1,
            :role,
            0,
            'active'
        )
    ");

    $insert->execute([
        ':username' => $username,
        ':display_name' => $displayName,
        ':password_hash' => $passwordHash,
        ':role' => $role
    ]);

    $newUserId = (int) $pdo->lastInsertId();

    /*
     * Permanently register the Creator.
     */
    if ($isFirstUser) {

        $updateCreator = $pdo->prepare("
            UPDATE system_settings
            SET setting_value = :creator_user_id
            WHERE setting_key = 'creator_user_id'
        ");

        $updateCreator->execute([
            ':creator_user_id' => $newUserId
        ]);
    }

    $pdo->commit();

    header('Location: ../login.html?registered=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
     * A duplicate username can still occur if two
     * registrations reach the database simultaneously.
     */
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        header('Location: ../register.html?error=taken');
        exit;
    }

    header('Location: ../register.html?error=register');
    exit;
}
