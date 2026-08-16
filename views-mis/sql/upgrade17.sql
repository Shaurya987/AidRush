-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v17
--
--   1 · fpos
--       The FPO (Farmer Producer Organisation) section, built from the
--       client's FPO Information sheet: where it is, how it is registered,
--       its office bearers, its shareholders, the support it has received
--       and five years of audited turnover.
--
--   2 · beneficiaries.bl_<source>_rate
--       The baseline table now carries a Rate per kilogram for each crop,
--       so the baseline income calculates itself as Production × Rate,
--       exactly the way Production & Output already does.
--
--   3 · beneficiaries.bl_<source>_count
--       The number of livestock or units for the non-farm livelihoods
--       (goats, poultry birds, enterprise units), so their income can be
--       judged against the size of the holding instead of appearing from
--       nowhere.
--
--   ADD-ONLY & idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_col17;
DELIMITER //
CREATE PROCEDURE _vmis_add_col17(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
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

-- ── Baseline: Rate per kg for the seven crop rows ───────────────────
CALL _vmis_add_col17('beneficiaries','bl_paddy_rate',     'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_millet_rate',    'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_vegetable_rate', 'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_tuber_rate',     'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_pulses_rate',    'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_oilseed_rate',   'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_mushroom_rate',  'DECIMAL(16,2) NULL');

-- ── Baseline: number of livestock / units for the non-farm rows ─────
CALL _vmis_add_col17('beneficiaries','bl_goat_count',            'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_poultry_count',         'DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_micro_enterprise_count','DECIMAL(16,2) NULL');
CALL _vmis_add_col17('beneficiaries','bl_other_count',           'DECIMAL(16,2) NULL');

DROP PROCEDURE IF EXISTS _vmis_add_col17;

-- ── FPO — Farmer Producer Organisations ─────────────────────────────
CREATE TABLE IF NOT EXISTS fpos (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  fpo_id                VARCHAR(32) NULL,
  programme_id          VARCHAR(32) NULL,
  project_id            VARCHAR(32) NULL,
  donor_id              VARCHAR(32) NULL,
  geography_id          VARCHAR(32) NULL,
  district              VARCHAR(96) NULL,
  block                 VARCHAR(96) NULL,
  gram_panchayat        VARCHAR(96) NULL,
  village               VARCHAR(96) NULL,
  fpo_name              VARCHAR(191) NULL,
  registration_type     VARCHAR(64) NULL,
  date_of_incorporation DATE NULL,
  address               VARCHAR(255) NULL,
  cin                   VARCHAR(64) NULL,
  pan                   VARCHAR(32) NULL,
  tan                   VARCHAR(32) NULL,
  gst_number            VARCHAR(32) NULL,
  no_of_bod_members     INT NULL,
  managing_director     VARCHAR(191) NULL,
  chairperson           VARCHAR(191) NULL,
  ceo_name              VARCHAR(191) NULL,
  contact_number        VARCHAR(32) NULL,
  mail_id               VARCHAR(191) NULL,
  no_of_shareholders    INT NULL,
  no_of_women_shareholders INT NULL,
  agm_conducted         VARCHAR(16) NULL,
  awards_received       TEXT NULL,
  supported_by          VARCHAR(191) NULL,
  scheme_name           VARCHAR(191) NULL,
  amount_supported      DECIMAL(18,2) NULL,
  turnover_y1           DECIMAL(18,2) NULL,
  turnover_y1_remarks   VARCHAR(255) NULL,
  turnover_y2           DECIMAL(18,2) NULL,
  turnover_y2_remarks   VARCHAR(255) NULL,
  turnover_y3           DECIMAL(18,2) NULL,
  turnover_y3_remarks   VARCHAR(255) NULL,
  turnover_y4           DECIMAL(18,2) NULL,
  turnover_y4_remarks   VARCHAR(255) NULL,
  turnover_y5           DECIMAL(18,2) NULL,
  turnover_y5_remarks   VARCHAR(255) NULL,
  remarks               TEXT NULL,
  created_by            VARCHAR(96) NULL,
  updated_by            VARCHAR(96) NULL,
  created_at            DATETIME NULL,
  updated_at            DATETIME NULL,
  UNIQUE KEY uq_fpo_id (fpo_id),
  KEY idx_fpo_project (project_id),
  KEY idx_fpo_district (district),
  KEY idx_fpo_block (block)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   · Self Help Groups is followed by a full FPO section
--   · the baseline table asks for a Rate per kilogram and works out the
--     income for you, and asks how many goats, birds or units the
--     household kept
-- ════════════════════════════════════════════════════════════════
