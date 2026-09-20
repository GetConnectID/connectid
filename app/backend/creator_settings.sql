CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings
(
    setting_key,
    setting_value
)
SELECT
    'creator_user_id',
    CAST(id AS CHAR)
FROM users
WHERE role = 'creator'
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = setting_value;
