UPDATE users
SET role = 'creator'
WHERE id = (
    SELECT id
    FROM (
        SELECT id
        FROM users
        ORDER BY id ASC
        LIMIT 1
    ) AS first_user
);
