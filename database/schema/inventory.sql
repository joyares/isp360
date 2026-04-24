-- Inventory master tables
-- ISP360: Units, Category, and Sub Category

CREATE TABLE IF NOT EXISTS inventory_units (
    unit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_inventory_units_name (unit_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_inventory_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_sub_categories (
    sub_category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    sub_category_name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_inventory_sub_categories_name (category_id, sub_category_name),
    KEY idx_inventory_sub_categories_category (category_id),
    CONSTRAINT fk_inventory_sub_categories_category FOREIGN KEY (category_id)
        REFERENCES inventory_categories(category_id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_products (
    product_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(180) NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    sub_category_id BIGINT UNSIGNED NOT NULL,
    unit_id BIGINT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_inventory_products_category (category_id),
    KEY idx_inventory_products_sub_category (sub_category_id),
    KEY idx_inventory_products_unit (unit_id),
    CONSTRAINT fk_inventory_products_category FOREIGN KEY (category_id)
        REFERENCES inventory_categories(category_id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_inventory_products_sub_category FOREIGN KEY (sub_category_id)
        REFERENCES inventory_sub_categories(sub_category_id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_inventory_products_unit FOREIGN KEY (unit_id)
        REFERENCES inventory_units(unit_id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO inventory_units (unit_name, sort_order, status)
SELECT 'Pcs', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_units WHERE unit_name = 'Pcs');

INSERT INTO inventory_units (unit_name, sort_order, status)
SELECT 'Box', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_units WHERE unit_name = 'Box');

INSERT INTO inventory_units (unit_name, sort_order, status)
SELECT 'Meter', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_units WHERE unit_name = 'Meter');

INSERT INTO inventory_categories (category_name, sort_order, status)
SELECT 'Networking', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_categories WHERE category_name = 'Networking');

INSERT INTO inventory_categories (category_name, sort_order, status)
SELECT 'Fiber Equipment', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_categories WHERE category_name = 'Fiber Equipment');

INSERT INTO inventory_categories (category_name, sort_order, status)
SELECT 'Accessories', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM inventory_categories WHERE category_name = 'Accessories');

INSERT INTO inventory_sub_categories (category_id, sub_category_name, sort_order, status)
SELECT c.category_id, 'Router', 10, 1
FROM inventory_categories c
WHERE c.category_name = 'Networking'
  AND NOT EXISTS (
      SELECT 1 FROM inventory_sub_categories sc
      WHERE sc.category_id = c.category_id AND sc.sub_category_name = 'Router'
  );

INSERT INTO inventory_sub_categories (category_id, sub_category_name, sort_order, status)
SELECT c.category_id, 'Switch', 20, 1
FROM inventory_categories c
WHERE c.category_name = 'Networking'
  AND NOT EXISTS (
      SELECT 1 FROM inventory_sub_categories sc
      WHERE sc.category_id = c.category_id AND sc.sub_category_name = 'Switch'
  );
