<?php

function addReputation(
    PDO $pdo,
    int $userId,
    int $points,
    string $type,
    string $reason,
    ?int $actorId = null,
    ?string $referenceType = null,
    ?int $referenceId = null
): bool {
    if ($points === 0) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO reputation_events
            (
                user_id,
                actor_id,
                type,
                points,
                reason,
                reference_type,
                reference_id
            )
            VALUES
            (
                :user_id,
                :actor_id,
                :type,
                :points,
                :reason,
                :reference_type,
                :reference_id
            )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':actor_id' => $actorId,
            ':type' => $type,
            ':points' => $points,
            ':reason' => $reason,
            ':reference_type' => $referenceType,
            ':reference_id' => $referenceId
        ]);

        $update = $pdo->prepare("
            UPDATE users
            SET reputation = reputation + :points
            WHERE id = :user_id
        ");

        $update->execute([
            ':points' => $points,
            ':user_id' => $userId
        ]);

        $pdo->commit();

        return true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}
