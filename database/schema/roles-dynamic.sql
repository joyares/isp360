-- ISP360 Roles screen schema
-- Keeps role create/edit form fields and selected menu tree persisted.

CREATE TABLE IF NOT EXISTS roles (
    role_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL,
    role_slug VARCHAR(120) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_roles_role_slug (role_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE roles ADD COLUMN IF NOT EXISTS role_type VARCHAR(60) NULL AFTER role_name;
ALTER TABLE roles ADD COLUMN IF NOT EXISTS role_description TEXT NULL AFTER role_type;
ALTER TABLE roles ADD COLUMN IF NOT EXISTS menu_tree_json JSON NULL AFTER role_description;
