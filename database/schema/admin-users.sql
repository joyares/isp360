-- ISP360 Admin Users Schema & Seed
-- No-Delete Policy: use status = 0 to deactivate.

CREATE TABLE IF NOT EXISTS admin_users (
    admin_user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id       BIGINT UNSIGNED NOT NULL,
    full_name     VARCHAR(150)    NOT NULL,
    username      VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    status        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_admin_users_username (username),
    KEY idx_admin_users_role_id (role_id),
    CONSTRAINT fk_admin_users_role_id
        FOREIGN KEY (role_id) REFERENCES roles (role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: Super Admin role (idempotent)
INSERT IGNORE INTO roles (role_name, role_slug, role_type, role_description, status)
VALUES ('Super Admin', 'super-admin', 'system', 'Full access system administrator', 1);

-- Seed: Admin user joyares (password: joyares)
-- Hash generated with PHP: password_hash('joyares', PASSWORD_BCRYPT)
INSERT IGNORE INTO admin_users (role_id, full_name, username, password_hash, status)
SELECT r.role_id,
       'Mostafa Joy',
       'joyares',
       '$2y$12$iH8lOdnrsVFp1HEpR04gqOrdolKq5PihkaAAScxGPOiktUvakBHhK',
       1
FROM roles r
WHERE r.role_slug = 'super-admin'
LIMIT 1;
