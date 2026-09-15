<?php

require_once __DIR__ . '/db.php';

function tooManyLoginAttempts(PDO $pdo, string $username, string $ip): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE created_at >= (NOW() - INTERVAL 15 MINUTE)
          AND successful = 0
          AND (username = :username OR ip_address = :ip)
    ");

    $stmt->execute([
        ':username' => $username,
        ':ip' => $ip
    ]);

    return (int) $stmt->fetchColumn() >= 10;
}
