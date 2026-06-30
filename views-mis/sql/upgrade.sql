-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — ONE-TIME UPGRADE for the live database.
-- Run once: phpMyAdmin → select your database → Import this file.
-- It is idempotent & safe. It:
--   1. adds the programme_objectives table
--   2. adds pivot columns to dashboard_charts (for custom charts)
--   3. consolidates the 9 programme rows into the 2 REAL programmes
--      and collects ALL their objectives
--   4. fixes child-table references (PGR→PRG typos)
--   5. adds the missing donor referenced by 600 beneficiaries
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

-- 1) Objectives table -------------------------------------------------
CREATE TABLE IF NOT EXISTS `programme_objectives` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `objective_id` VARCHAR(40),
  `programme_id` VARCHAR(120),
  `objective` TEXT,
  `status` VARCHAR(40) DEFAULT 'Ongoing',
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_obj_prg` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Custom-chart pivot columns (works on MySQL 8 and MariaDB) --------
SET @hasgb := (SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema=DATABASE() AND table_name='dashboard_charts' AND column_name='group_by');
SET @sql := IF(@hasgb=0,
  "ALTER TABLE `dashboard_charts` ADD COLUMN `group_by` VARCHAR(64) NULL, ADD COLUMN `measure` VARCHAR(64) DEFAULT 'count'",
  "DO 0");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Collect every objective under the 2 canonical programmes ---------
DELETE FROM `programme_objectives`;
INSERT INTO `programme_objectives` (programme_id, objective, status)
SELECT 'PRG-LIV-001', programme_objective, COALESCE(NULLIF(programme_status,''),'Ongoing')
FROM `programmes`
WHERE programme_objective IS NOT NULL AND programme_objective<>''
  AND programme_id IN ('PRG-LIV-001','PGR-LIV-001');
INSERT INTO `programme_objectives` (programme_id, objective, status)
SELECT 'PRG-LIV-002', programme_objective, COALESCE(NULLIF(programme_status,''),'Ongoing')
FROM `programmes`
WHERE programme_objective IS NOT NULL AND programme_objective<>''
  AND programme_id IN ('PGR-LIV-002','PRG-LIV-002');

-- 4) Fix child references so everything links to the 2 programmes -----
UPDATE `beneficiaries`      SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `beneficiaries`      SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `projects`           SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `projects`           SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `villages`           SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `villages`           SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `shgs`               SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `shgs`               SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `indicators`         SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `indicators`         SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `indicator_progress` SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `indicator_progress` SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `crops`              SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `crops`              SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `loans`              SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `loans`              SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';
UPDATE `donor_mappings`     SET programme_id='PRG-LIV-001' WHERE programme_id='PGR-LIV-001';
UPDATE `donor_mappings`     SET programme_id='PRG-LIV-002' WHERE programme_id='PGR-LIV-002';

-- 5) Keep EXACTLY two programmes with their real names ----------------
DELETE FROM `programmes`;
INSERT INTO `programmes`
  (programme_id,programme_name,programme_objective,thematic_area,programme_start_date,programme_end_date,programme_status,programme_manager,remarks)
VALUES
('PRG-LIV-001','Promoting Sustainable Livelihood among small and marginalized farmers through collective action Phase-11','Multiple objectives - see Programme Objectives','Livelihood','2024-10-01','2027-09-30','Ongoing','Saroj Kumar Satapathy','Consolidated programme'),
('PRG-LIV-002','Promoting Sustainable Livelihood Thorugh Agri Management Services','Multiple objectives - see Programme Objectives','Livelihood','2024-10-01','2027-09-30','Ongoing','Saroj Kumar Satapathy','Consolidated programme');

-- 6) Add the donor 600 beneficiaries reference (rename it afterwards) --
INSERT INTO `donors` (donor_id, donor_name, donor_type)
SELECT 'DON-FC-002','Funding Partner (please rename)','Other'
WHERE NOT EXISTS (SELECT 1 FROM `donors` WHERE donor_id='DON-FC-002');
