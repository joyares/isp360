-- Settings Table
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(32) NOT NULL,
  `key` VARCHAR(64) NOT NULL,
  `value` TEXT,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY `type_key` (`type`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
