-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v14
--   1) LOCAL GOVERNANCE module (from the client's Convergence sheet):
--        • gram_sabha    — VDC / Gram Sabha participation records
--        • convergence   — government schemes leveraged (dept, scheme,
--                          HHs benefited, amount mobilised)
--   2) `users.assigned_projects` — restrict a data-entry user to
--      specific project(s); empty = all projects (admins always all).
--   3) `crops.food_security` — "food security throughout the year?"
--      (Yes/No) — feeds logged indicator #2.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `gram_sabha` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gramsabha_id` VARCHAR(64),
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `geography_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village` TEXT,
  `vdc_name` TEXT,
  `total_participants` INT,
  `male_participants` INT,
  `female_participants` INT,
  `vdp_submitted` VARCHAR(10),
  `meeting_date` VARCHAR(40),
  `remarks` TEXT,
  `created_by` VARCHAR(60),
  `updated_by` VARCHAR(60),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_gs_gid` (`gramsabha_id`),
  KEY `idx_gs_project` (`project_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `convergence` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `convergence_id` VARCHAR(64),
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `geography_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village` TEXT,
  `department` TEXT,
  `scheme_name` TEXT,
  `work_type` TEXT,
  `hh_benefited` INT,
  `amount_mobilised` DECIMAL(16,2),
  `remarks` TEXT,
  `created_by` VARCHAR(60),
  `updated_by` VARCHAR(60),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_cv_cid` (`convergence_id`),
  KEY `idx_cv_project` (`project_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col14;
DELIMITER //
CREATE PROCEDURE _vmis_add_col14(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = tbl) > 0
     AND (SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col) = 0 THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', ddl);
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END //
DELIMITER ;

CALL _vmis_add_col14('users','assigned_projects','TEXT NULL');
CALL _vmis_add_col14('crops','food_security','VARCHAR(10) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col14;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • Local Governance appears in the left nav: Gram Sabha / VDC and
--     Convergence — full sections with forms, filters, export & import.
--   • Users & Access can pin a data-entry user to specific project(s);
--     the server then filters every project-linked list and refuses
--     writes to any other project for that user.
--   • The M&E section becomes the 11 LOGGED INDICATORS — computed
--     automatically from the data already entered (no manual typing).
-- ════════════════════════════════════════════════════════════════
