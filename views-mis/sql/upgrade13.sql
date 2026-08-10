-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v13
--   1) BASELINE per source: Expenditure (and Production kg for crops)
--      alongside the existing Area & Income — so NET income
--      (income − expenditure) is known at baseline, exactly like the
--      Production side already knows Net Profit.
--      NET IS NEVER STORED — it is always computed (income − expenditure),
--      so the two figures can never drift apart.
--   2) `organic_farming` on Beneficiaries AND Production — the
--      before/after counts the Organic Farming indicator reports on.
--   3) `sold` on Production — replaces self-consumption (Yes/No).
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
--   Existing rows keep working: where no expenditure is recorded,
--   net simply equals the income already entered.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col13;
DELIMITER //
CREATE PROCEDURE _vmis_add_col13(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

-- ── Baseline CROPS: expenditure + production (kg). Area & income already exist ──
CALL _vmis_add_col13('beneficiaries','bl_paddy_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_paddy_kg','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_millet_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_millet_kg','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_vegetable_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_vegetable_kg','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_tuber_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_tuber_kg','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_pulses_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_pulses_kg','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_oilseed_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_oilseed_kg','DECIMAL(16,2) NULL');

-- ── Baseline OTHER livelihoods: expenditure (income already exists) ──
CALL _vmis_add_col13('beneficiaries','bl_mushroom_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_goat_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_poultry_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_micro_enterprise_expenditure','DECIMAL(16,2) NULL');
CALL _vmis_add_col13('beneficiaries','bl_other_expenditure','DECIMAL(16,2) NULL');

-- ── Organic farming: BEFORE (at registration) and AFTER (each production record) ──
CALL _vmis_add_col13('beneficiaries','organic_farming','VARCHAR(10) NULL');
CALL _vmis_add_col13('crops','organic_farming','VARCHAR(10) NULL');

-- ── Produce sold? (replaces the old self-consumption quantity) ──
CALL _vmis_add_col13('crops','sold','VARCHAR(10) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col13;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • The Beneficiary baseline becomes ONE tidy table — per source:
--     Area · Production (kg) · Income · Expenditure · Net (auto).
--   • Organic Farming (Yes/No) is captured at registration and on every
--     production record, so "before vs now" is countable in reports.
--   • Production asks "Produce sold?" instead of a self-consumption qty.
--   • Every income comparison prefers NET (income − expenditure) and
--     falls back to gross income wherever expenditure was not recorded.
-- ════════════════════════════════════════════════════════════════
