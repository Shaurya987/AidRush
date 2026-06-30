-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v6
--   HQ-level planning targets (the "At HQ level" tier of the new
--   two-tier Data Entry). HQ sets, per Donor × Thematic × Project ×
--   District, the planned targets for Blocks, Villages, Indicators,
--   Beneficiaries, Activities and Output/Production. Field Offices
--   then enter the actuals, and the forms show the target alongside.
--
--   ADD-ONLY: creates ONE new table. It DROPS NOTHING and touches no
--   existing data. Fully idempotent — safe to run as many times as
--   you like (CREATE TABLE IF NOT EXISTS).
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file
-- (or paste into the SQL tab and Go).
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `hq_targets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `target_id` VARCHAR(64),
  `donor_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `district` TEXT,
  `financial_year` VARCHAR(40),
  `target_blocks` INT,
  `target_villages` INT,
  `target_indicators` INT,
  `target_beneficiaries` INT,
  `target_activities` INT,
  `target_output` DECIMAL(16,2),
  `output_unit` VARCHAR(80),
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_hq_targets_target_id` (`target_id`(32)),
  KEY `idx_hq_targets_project_id` (`project_id`(64)),
  KEY `idx_hq_targets_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • Data Entry gains two tiers: "HQ Setup & Targets" and
--     "Field Office Entry".
--   • HQ Setup → pick Donor / Thematic / Project, then add one or more
--     Districts, each with its planned targets. Each district is saved
--     as one hq_targets row (target_id auto = HQT-0001, …).
--   • Field Office Entry → pick District + Project once; Donor & Thematic
--     show automatically; Block / Gram Panchayat / Village are set once
--     and reused, so every Beneficiary / SHG / Output / Activity /
--     Indicator-Progress record inherits the context without re-typing.
--   • The Indicator-Progress and Activity forms display the matching HQ
--     target automatically.
--   • Nothing else changes; existing reports keep working unchanged.
-- ════════════════════════════════════════════════════════════════
