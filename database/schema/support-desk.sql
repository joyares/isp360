-- ISP360 Support Desk Schema & Seed
-- No-Delete Policy: use status = 0 to deactivate records.

CREATE TABLE IF NOT EXISTS support_ticket_categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_support_ticket_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_priorities (
    priority_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    priority_name VARCHAR(80) NOT NULL,
    color VARCHAR(20) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_support_ticket_priorities_name (priority_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_statuses (
    ticket_status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_support_ticket_statuses_name (status_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
    ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_no VARCHAR(40) NOT NULL,
    ticket_for VARCHAR(40) NOT NULL DEFAULT 'existing_customer',
    customer_id BIGINT UNSIGNED NULL,
    issue_details TEXT NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    priority_id BIGINT UNSIGNED NULL,
    ticket_status_id BIGINT UNSIGNED NULL,
    assigned_employee_id BIGINT UNSIGNED NULL,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_support_tickets_ticket_no (ticket_no),
    KEY idx_support_tickets_status (status),
    KEY idx_support_tickets_ticket_status (ticket_status_id),
    KEY idx_support_tickets_priority (priority_id),
    KEY idx_support_tickets_assigned (assigned_employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_notes (
    ticket_note_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    note_text TEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_support_ticket_notes_ticket (ticket_id),
    KEY idx_support_ticket_notes_status (status),
    KEY idx_support_ticket_notes_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure customers table has fields used by support desk list query.
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'username');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN username VARCHAR(120) NULL AFTER customer_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'phone_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN phone_no VARCHAR(30) NULL AFTER username', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO support_ticket_categories (category_name, sort_order, status) VALUES
    ('Connection', 10, 1),
    ('Billing', 20, 1),
    ('Technical', 30, 1),
    ('General', 40, 1);

INSERT IGNORE INTO support_ticket_priorities (priority_name, color, sort_order, status) VALUES
    ('Low', '#6c757d', 10, 1),
    ('Medium', '#0d6efd', 20, 1),
    ('High', '#fd7e14', 30, 1),
    ('Urgent', '#dc3545', 40, 1);

INSERT IGNORE INTO support_ticket_statuses (status_name, sort_order, status) VALUES
    ('Open', 10, 1),
    ('In Progress', 20, 1),
    ('Resolved', 30, 1),
    ('Closed', 40, 1);
