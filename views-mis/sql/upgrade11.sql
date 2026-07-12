-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v11
--   ACTIVITY PROGRESS multi-year table form:
--   1) Years 4–6 for every activity — target, Q1–Q4 and the year's
--      achievement (Years 1–3 already exist in the base schema).
--   2) `monthly_breakup` — when an activity is entered MONTHLY, the
--      12 per-month figures are kept here (as JSON) so re-opening the
--      record shows the months again. The months always roll up into
--      the quarterly columns too, so every report keeps working.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col11;
DELIMITER //
CREATE PROCEDURE _vmis_add_col11(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

-- Year 4
CALL _vmis_add_col11('activities','target_year_4','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y4_q1','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y4_q2','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y4_q3','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y4_q4','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y4_total_achievement','DECIMAL(16,2) NULL');
-- Year 5
CALL _vmis_add_col11('activities','target_year_5','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y5_q1','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y5_q2','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y5_q3','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y5_q4','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y5_total_achievement','DECIMAL(16,2) NULL');
-- Year 6
CALL _vmis_add_col11('activities','target_year_6','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y6_q1','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y6_q2','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y6_q3','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y6_q4','DECIMAL(16,2) NULL');
CALL _vmis_add_col11('activities','y6_total_achievement','DECIMAL(16,2) NULL');
-- Monthly figures (JSON) — only used when the officer picks "Monthly"
CALL _vmis_add_col11('activities','monthly_breakup','TEXT NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col11;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • The Activity Progress form saves up to 6 years, each with its
--     own target + quarterly (or monthly) achievements.
--   • Monthly entries reopen exactly as typed; their quarter roll-ups
--     keep every existing report and export correct.
-- ════════════════════════════════════════════════════════════════
