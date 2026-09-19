<?php

function createNotification(
    PDO $pdo,
    int $userId,
    string $type,
    string $title,
    string $message,
    ?string $referenceType = null,
    ?int $referenceId = null
): bool {
    if ($userId <= 0 || $type === '' || $title === '' || $message === '') {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications
            (
                user_id,
                type,
                title,
                message,
                reference_type,
                reference_id
            )
            VALUES
            (
                :user_id,
                :type,
                :title,
                :message,
                :reference_type,
                :reference_id
            )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message,
            ':reference_type' => $referenceType,
            ':reference_id' => $referenceId
        ]);

        return true;

    } catch (Throwable $e) {
        return false;
    }
}
