-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v10
--   1) PROJECT ↔ GEOGRAPHY link: `geographies.project_id` — every place
--      belongs to a project, so filtering the dashboard by project shows
--      its districts & blocks, and each project lists its geographies.
--   2) `beneficiaries.alternative_livelihood` — the Alt. Livelihood
--      field on the Beneficiary form (Production already has it).
--   3) UNIQUE indexes on every business ID (BEN-0001, PRJ-001, …) so a
--      duplicate ID can NEVER be created again. Each index is added
--      ONLY if that table currently has no duplicates — tables with
--      legacy duplicates (e.g. two donors sharing DON-CSR-001) are
--      skipped until you fix the data, then simply re-run this file.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col10;
DELIMITER //
CREATE PROCEDURE _vmis_add_col10(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col10('geographies','project_id','VARCHAR(64) NULL');
CALL _vmis_add_col10('beneficiaries','alternative_livelihood','VARCHAR(120) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col10;

-- ── UNIQUE business-ID indexes (added only where the data is already clean) ──
--    The index form adapts to each column: short VARCHARs are indexed whole,
--    TEXT/long columns get a safe 64-char prefix, non-string columns are
--    indexed whole — so MySQL error #1089 (bad prefix key) can never occur.
DROP PROCEDURE IF EXISTS _vmis_uniq10;
DELIMITER //
CREATE PROCEDURE _vmis_uniq10(IN tbl VARCHAR(64), IN col VARCHAR(64))
BEGIN
  DECLARE dtype VARCHAR(64) DEFAULT '';
  DECLARE clen  BIGINT      DEFAULT 0;
  IF (SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = tbl) > 0
     AND (SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col) > 0
     AND (SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = tbl
          AND index_name = CONCAT('uq_', col)) = 0 THEN
    SELECT DATA_TYPE, COALESCE(CHARACTER_MAXIMUM_LENGTH,0) INTO dtype, clen
      FROM information_schema.columns
      WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col
      LIMIT 1;
    SET @q = CONCAT('SELECT COUNT(*) INTO @d FROM (SELECT `',col,'` FROM `',tbl,
      '` WHERE `',col,'` IS NOT NULL AND `',col,'`<>'''' GROUP BY `',col,'` HAVING COUNT(*)>1) t');
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
    IF @d = 0 THEN
      IF dtype IN ('varchar','char') AND clen > 0 AND clen <= 191 THEN
        SET @sql = CONCAT('ALTER TABLE `',tbl,'` ADD UNIQUE INDEX `uq_',col,'` (`',col,'`)');
      ELSEIF dtype IN ('varchar','char','text','tinytext','mediumtext','longtext') THEN
        SET @sql = CONCAT('ALTER TABLE `',tbl,'` ADD UNIQUE INDEX `uq_',col,'` (`',col,'`(64))');
      ELSE
        SET @sql = CONCAT('ALTER TABLE `',tbl,'` ADD UNIQUE INDEX `uq_',col,'` (`',col,'`)');
      END IF;
      PREPARE s2 FROM @sql; EXECUTE s2; DEALLOCATE PREPARE s2;
    END IF;
  END IF;
END //
DELIMITER ;

CALL _vmis_uniq10('programmes','programme_id');
CALL _vmis_uniq10('projects','project_id');
CALL _vmis_uniq10('donors','donor_id');
CALL _vmis_uniq10('donor_mappings','mapping_id');
CALL _vmis_uniq10('geographies','geography_id');
CALL _vmis_uniq10('villages','demographic_id');
CALL _vmis_uniq10('beneficiaries','beneficiary_id');
CALL _vmis_uniq10('crops','production_record_id');
CALL _vmis_uniq10('activities','activity_id');
CALL _vmis_uniq10('shgs','shg_id');
CALL _vmis_uniq10('loans','loan_record_id');
CALL _vmis_uniq10('indicators','indicator_id');
CALL _vmis_uniq10('indicator_progress','progress_id');
CALL _vmis_uniq10('hq_targets','target_id');
CALL _vmis_uniq10('workspaces','workspace_id');
CALL _vmis_uniq10('shg_members','member_id');

DROP PROCEDURE IF EXISTS _vmis_uniq10;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • Projects gain a 🌍 Geographies manager; the Geography section
--     shows and filters by Project; project-filtered dashboards count
--     the right districts & blocks.
--   • The Beneficiary form gains Alternative Livelihood.
--   • Duplicate business IDs become IMPOSSIBLE on every clean table
--     (the database itself refuses them). Fix any flagged legacy
--     duplicates (see the Donors page banner), re-run this file, and
--     the protection covers those tables too.
-- ════════════════════════════════════════════════════════════════
