-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v15
--   Mushroom Cultivation joins the CROP rows of the baseline table:
--   it now carries Area (acre) and Production (kg) alongside its
--   Income and Expenditure, exactly like paddy / millet / vegetable.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col15;
DELIMITER //
CREATE PROCEDURE _vmis_add_col15(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col15('beneficiaries','bl_mushroom_area','DECIMAL(16,2) NULL');
CALL _vmis_add_col15('beneficiaries','bl_mushroom_kg','DECIMAL(16,2) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col15;

-- ════════════════════════════════════════════════════════════════
-- After running this, the Mushroom Cultivation row of the baseline
-- table accepts Area and Production (kg); both feed Total Land and
-- the productivity/area indicators just like the other crops.
-- ════════════════════════════════════════════════════════════════
