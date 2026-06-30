-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v7
--   Per-user work assignment. Adds `assigned_to` to hq_targets so HQ
--   can assign each planned work unit (Project × District) to a named
--   field officer. Officers then see a "My assignments" panel and
--   start a workspace from it in one click.
--
--   ADD-ONLY & idempotent — adds one nullable column only if missing.
--   DROPS NOTHING. Safe to run repeatedly. (If you imported upgrade6
--   AFTER this column was already added, that's fine too.)
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- (Run upgrade6.sql first so the hq_targets table exists.)
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col7;
DELIMITER //
CREATE PROCEDURE _vmis_add_col7(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col7('hq_targets','assigned_to','VARCHAR(190) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col7;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • HQ Setup gains an "Assigned to (user)" field on each district row.
--   • Field-Office Entry gains a "My assignments" panel — each officer
--     sees the work units assigned to them and starts a workspace in
--     one click (project + district pre-filled).
-- ════════════════════════════════════════════════════════════════
