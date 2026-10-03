<?php

function tooManyLoginAttempts(PDO $pdo, string $username, string $ipAddress): bool
{
    $windowMinutes = 15;

    /*
     * Limiet per account:
     * maximaal 5 mislukte pogingen per 15 minuten.
     */
    $maxUsernameAttempts = 5;

    /*
     * Limiet per IP:
     * maximaal 20 mislukte pogingen per 15 minuten.
     *
     * Dit voorkomt dat één toestel eindeloos probeert,
     * maar blokkeert niet meteen alle accounts na 5 fouten.
     */
    $maxIpAttempts = 20;

    $cutoff = date(
        'Y-m-d H:i:s',
        time() - ($windowMinutes * 60)
    );

    /*
     * Limiet per username.
     */
    if ($username !== '') {

        $usernameStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM login_attempts
            WHERE successful = 0
            AND created_at >= :username_cutoff
            AND username = :username
        ");

        $usernameStmt->execute([
            ':username_cutoff' => $cutoff,
            ':username' => $username
        ]);

        $usernameAttempts = (int) $usernameStmt->fetchColumn();

        if ($usernameAttempts >= $maxUsernameAttempts) {
            return true;
        }
    }

    /*
     * Limiet per IP.
     */
    $ipStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE successful = 0
        AND created_at >= :ip_cutoff
        AND ip_address = :ip_address
    ");

    $ipStmt->execute([
        ':ip_cutoff' => $cutoff,
        ':ip_address' => $ipAddress
    ]);

    $ipAttempts = (int) $ipStmt->fetchColumn();

    if ($ipAttempts >= $maxIpAttempts) {
        return true;
    }

    return false;
}
