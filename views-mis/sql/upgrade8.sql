-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v8
--   Multi-user accountability: every record remembers WHO entered it
--   and who last changed it. Adds two nullable columns —
--   `created_by` and `updated_by` (the username) — to every business
--   table. The API stamps them automatically on save; the edit form
--   shows "Added by … · Last updated by …", and every Excel export
--   carries them.
--
--   ADD-ONLY & idempotent — each column is added only if missing.
--   DROPS NOTHING. Safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col8;
DELIMITER //
CREATE PROCEDURE _vmis_add_col8(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col8('programmes','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('programmes','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('programme_objectives','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('programme_objectives','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('project_objectives','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('project_objectives','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('projects','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('projects','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('donors','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('donors','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('donor_mappings','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('donor_mappings','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('geographies','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('geographies','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('villages','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('villages','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('beneficiaries','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('beneficiaries','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('crops','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('crops','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('activities','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('activities','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('shgs','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('shgs','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('shg_members','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('shg_members','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('loans','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('loans','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('indicators','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('indicators','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('indicator_progress','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('indicator_progress','updated_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('hq_targets','created_by','VARCHAR(190) NULL');
CALL _vmis_add_col8('hq_targets','updated_by','VARCHAR(190) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col8;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • Every new save stamps created_by; every edit stamps updated_by.
--   • The edit form shows "Added by … · Last updated by …".
--   • Excel exports (master workbook / raw section exports) include
--     the two columns automatically.
--   • Existing rows simply show a blank until first edited. Nothing
--     else changes.
-- ════════════════════════════════════════════════════════════════
