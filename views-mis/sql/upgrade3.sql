-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v3
--   SHG member roster — track every member by name, role, age,
--   contact, savings contribution, etc.  Mirrors how programme &
--   project objectives are stored (sub-table linked by the parent's
--   business ID).  Idempotent — safe to re-run.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `shg_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` VARCHAR(40),
  `shg_id` VARCHAR(120),
  `member_name` VARCHAR(160) NOT NULL,
  `father_or_spouse_name` VARCHAR(160),
  `role` VARCHAR(60) DEFAULT 'Member',
  `gender` VARCHAR(20),
  `age` INT,
  `caste` VARCHAR(40),
  `contact_number` VARCHAR(40),
  `savings_contribution_inr` DECIMAL(12,2),
  `date_joined` DATE,
  `status` VARCHAR(40) DEFAULT 'Active',
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sm_shg`  (`shg_id`(64)),
  KEY `idx_sm_name` (`member_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
