-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v12
--   Beneficiary BASELINE income: three more crops (with land area)
--   and an "Other" income line —
--     • Tuber Crop  — area + income
--     • Pulses      — area + income
--     • Oilseeds    — area + income
--     • Other       — income only
--   All optional. They join the existing breakdown everywhere:
--   the form's auto totals, the income view, the dashboard
--   Before-vs-After comparison and the Income Impact report.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col12;
DELIMITER //
CREATE PROCEDURE _vmis_add_col12(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col12('beneficiaries','bl_tuber_area','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_tuber_income','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_pulses_area','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_pulses_income','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_oilseed_area','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_oilseed_income','DECIMAL(16,2) NULL');
CALL _vmis_add_col12('beneficiaries','bl_other_income','DECIMAL(16,2) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col12;

-- ════════════════════════════════════════════════════════════════
-- After running this, the Beneficiary form gains Tuber Crop, Pulses,
-- Oilseeds (each with its land area) and Other income in the baseline
-- section — all auto-summed into Total Baseline Income and reflected
-- in every income view, chart and report.
-- ════════════════════════════════════════════════════════════════
