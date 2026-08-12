-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v16
--
--   crops.income_mode
--       Production & Output can now record income in TWO honest ways:
--         "Production (output × rate)"  the weighed crops
--         "Direct income"               goat rearing, backyard poultry,
--                                       micro enterprise, other income —
--                                       livelihoods that have no kilograms
--                                       and no price per kilogram
--       Older rows are read correctly without this column being filled:
--       no quantity and no rate but an income present is treated as a
--       directly entered figure.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col16;
DELIMITER //
CREATE PROCEDURE _vmis_add_col16(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

CALL _vmis_add_col16('crops','income_mode','VARCHAR(48) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col16;

-- ── Backfill the mode for records entered before it existed ──────────
--    A row with a quantity or a rate was clearly a weighed production.
UPDATE crops
   SET income_mode = 'Production (output × rate)'
 WHERE (income_mode IS NULL OR TRIM(income_mode) = '')
   AND (COALESCE(production_kg,0) <> 0 OR COALESCE(rate_inr_per_kg,0) <> 0);

--    A row with money but nothing weighed was clearly a typed figure.
UPDATE crops
   SET income_mode = 'Direct income'
 WHERE (income_mode IS NULL OR TRIM(income_mode) = '')
   AND COALESCE(production_kg,0) = 0
   AND COALESCE(rate_inr_per_kg,0) = 0
   AND (COALESCE(income_inr,0) <> 0 OR COALESCE(net_profit_inr,0) <> 0);

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   · the Production & Output form asks how the income was arrived at,
--     and hides Output Quantity and Rate when the answer is Direct income
--   · Goat Rearing, Backyard Poultry, Micro Enterprise and Other Income
--     take a typed rupee figure instead of a quantity and a rate
-- ════════════════════════════════════════════════════════════════
