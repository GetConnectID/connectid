<?php

function tooManyLoginAttempts(PDO $pdo, string $username, string $ipAddress): bool
{
    $windowMinutes = 15;
    $maxAttempts = 5;

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE successful = 0
        AND created_at >= (NOW() - INTERVAL :minutes MINUTE)
        AND (username = :username OR ip_address = :ip_address)
    ");

    $stmt->execute([
        ':minutes' => $windowMinutes,
        ':username' => $username,
        ':ip_address' => $ipAddress
    ]);

    return (int) $stmt->fetchColumn() >= $maxAttempts;
}
