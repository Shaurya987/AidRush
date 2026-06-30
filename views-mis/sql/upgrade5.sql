-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v5
--   Baseline livelihood income (at registration) — per-activity
--   breakdown on the beneficiaries table. Lets the MIS compare a
--   beneficiary's income BEFORE the project (baseline, captured on the
--   beneficiary form) with their CURRENT income (summed automatically
--   from their Production / Output records) and show "Income Enhanced".
--
--   ADD-ONLY: introduces 10 new nullable columns. It DROPS NOTHING and
--   touches no existing data. Existing `current_income_per_annum_inr`
--   (the registration annual income) is left exactly as it is.
--
--   Fully idempotent — every column is added only if it does not already
--   exist. Safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file
-- (or paste into the SQL tab and Go).
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col;
DELIMITER //
CREATE PROCEDURE _vmis_add_col(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

-- ─── Baseline livelihood income at registration (beneficiaries) ───
-- Crops carry an AREA + INCOME; the other livelihoods carry INCOME only.
CALL _vmis_add_col('beneficiaries','bl_paddy_area',            'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_paddy_income',          'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_millet_area',           'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_millet_income',         'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_vegetable_area',        'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_vegetable_income',      'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_mushroom_income',       'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_goat_income',           'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_poultry_income',        'DECIMAL(16,2) NULL');
CALL _vmis_add_col('beneficiaries','bl_micro_enterprise_income','DECIMAL(16,2) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • The Beneficiary form shows a "Baseline livelihood income — at
--     registration" section (Paddy/Millet/Vegetable with area + income,
--     and Mushroom / Goat / Poultry / Micro-Enterprise income) with a
--     live Total Baseline Income.
--   • The Excel export gains an "Income Impact (Baseline vs Current)"
--     sheet: baseline (from here) vs current (summed from Production /
--     Output, cumulative across all years) and Income Enhanced.
--   • Nothing else changes; existing reports keep working unchanged.
-- ════════════════════════════════════════════════════════════════
