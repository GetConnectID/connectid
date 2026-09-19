<?php

function tooManyLoginAttempts(PDO $pdo, string $username, string $ipAddress): bool
{
    $windowMinutes = 15;
    $maxAttempts = 5;

    $cutoff = date(
        'Y-m-d H:i:s',
        time() - ($windowMinutes * 60)
    );

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE successful = 0
        AND created_at >= :cutoff
        AND (
            username = :username
            OR ip_address = :ip_address
        )
    ");

    $stmt->execute([
        ':cutoff' => $cutoff,
        ':username' => $username,
        ':ip_address' => $ipAddress
    ]);

    return (int) $stmt->fetchColumn() >= $maxAttempts;
}
