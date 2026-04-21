-- ISP360 Locations table
-- Supports District > Thana/Area > Sub area hierarchy
-- No-Delete Policy: use status = 0 to deactivate.

CREATE TABLE IF NOT EXISTS locations (
    location_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(150) NOT NULL,
    location_type ENUM('district', 'thana-area', 'sub-area') NOT NULL,
    parent_location_id BIGINT UNSIGNED NULL,
    status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=On, 0=Off',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_locations_parent (parent_location_id),
    KEY idx_locations_type (location_type),
    CONSTRAINT fk_locations_parent
        FOREIGN KEY (parent_location_id)
        REFERENCES locations (location_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional starter data (idempotent by check)
INSERT INTO locations (location_name, location_type, parent_location_id, status)
SELECT 'Dhaka', 'district', NULL, 1
WHERE NOT EXISTS (
    SELECT 1 FROM locations WHERE location_name = 'Dhaka' AND location_type = 'district'
);

INSERT INTO locations (location_name, location_type, parent_location_id, status)
SELECT 'Mirpur', 'thana-area', d.location_id, 1
FROM locations d
WHERE d.location_name = 'Dhaka' AND d.location_type = 'district'
  AND NOT EXISTS (
      SELECT 1 FROM locations WHERE location_name = 'Mirpur' AND location_type = 'thana-area'
  )
LIMIT 1;

INSERT INTO locations (location_name, location_type, parent_location_id, status)
SELECT 'Section 10', 'sub-area', t.location_id, 1
FROM locations t
WHERE t.location_name = 'Mirpur' AND t.location_type = 'thana-area'
  AND NOT EXISTS (
      SELECT 1 FROM locations WHERE location_name = 'Section 10' AND location_type = 'sub-area'
  )
LIMIT 1;
