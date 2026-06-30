-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v4
--   Performance indexes for the Field-Data tables + every FK / filter
--   column the app queries on. This is the production-side half of the
--   "Beneficiaries / Production / Activities load slowly / failed to
--   fetch" fix — filtered lists and name-enrichment joins become fast.
--
--   Fully idempotent: each index is created only if it does not already
--   exist. Safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → Import (or paste into the SQL tab).
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _vmis_add_index;
DELIMITER //
CREATE PROCEDURE _vmis_add_index(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN cols VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = tbl) > 0
     AND (SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = tbl AND index_name = idx) = 0 THEN
    SET @sql = CONCAT('CREATE INDEX `', idx, '` ON `', tbl, '`(', cols, ')');
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END //
DELIMITER ;

-- ─── Beneficiaries (largest table — filtered by programme/project/donor) ───
CALL _vmis_add_index('beneficiaries','idx_ben_prg','`programme_id`');
CALL _vmis_add_index('beneficiaries','idx_ben_prj','`project_id`');
CALL _vmis_add_index('beneficiaries','idx_ben_don','`donor_id`');

-- ─── Production / Outputs (crops) ───
CALL _vmis_add_index('crops','idx_crop_prj','`project_id`');
CALL _vmis_add_index('crops','idx_crop_don','`donor_id`');
CALL _vmis_add_index('crops','idx_crop_ben','`beneficiary_id`');
CALL _vmis_add_index('crops','idx_crop_prg','`programme_id`');

-- ─── Activities & Targets ───
CALL _vmis_add_index('activities','idx_act_prj','`project_id`');
CALL _vmis_add_index('activities','idx_act_don','`donor_id`');
CALL _vmis_add_index('activities','idx_act_prg','`programme_id`');

-- ─── Indicators + Indicator Progress (new programme/project filters) ───
CALL _vmis_add_index('indicators','idx_ind_prg','`programme_id`');
CALL _vmis_add_index('indicators','idx_ind_prj','`project_id`');
CALL _vmis_add_index('indicators','idx_ind_don','`donor_id`');
CALL _vmis_add_index('indicator_progress','idx_prog_prg','`programme_id`');
CALL _vmis_add_index('indicator_progress','idx_prog_prj','`project_id`');
CALL _vmis_add_index('indicator_progress','idx_prog_ind','`indicator_id`');

-- ─── Geography + Village demographics ───
CALL _vmis_add_index('geographies','idx_geo_prg','`programme_id`');
CALL _vmis_add_index('geographies','idx_geo_prj','`project_id`');
CALL _vmis_add_index('villages','idx_vil_prg','`programme_id`');
CALL _vmis_add_index('villages','idx_vil_prj','`project_id`');
CALL _vmis_add_index('villages','idx_vil_don','`donor_id`');

-- ─── SHG, Loans, Mappings ───
CALL _vmis_add_index('shgs','idx_shg_prj','`project_id`');
CALL _vmis_add_index('shgs','idx_shg_don','`donor_id`');
CALL _vmis_add_index('shgs','idx_shg_prg','`programme_id`');
CALL _vmis_add_index('loans','idx_loan_shg','`shg_id`');
CALL _vmis_add_index('loans','idx_loan_prg','`programme_id`');
CALL _vmis_add_index('loans','idx_loan_prj','`project_id`');
CALL _vmis_add_index('loans','idx_loan_don','`donor_id`');
CALL _vmis_add_index('donor_mappings','idx_map_don','`donor_id`');
CALL _vmis_add_index('donor_mappings','idx_map_prj','`project_id`');
CALL _vmis_add_index('donor_mappings','idx_map_prg','`programme_id`');

-- ─── Objectives (count badges + in-section managers) ───
CALL _vmis_add_index('programme_objectives','idx_pgobj_prg','`programme_id`');
CALL _vmis_add_index('project_objectives','idx_pobj_prj2','`project_id`');
CALL _vmis_add_index('project_objectives','idx_pobj_prg','`programme_id`');

-- ─── Users — fast "people online" count + session lookups ───
CALL _vmis_add_index('users','idx_users_expires','`session_expires_at`');

DROP PROCEDURE IF EXISTS _vmis_add_index;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • Field-Data lists (Beneficiaries / Outputs / Activities) load fast
--     even with thousands of rows and any filter applied.
--   • Indicator & Indicator-Progress programme/project filters are indexed.
--   • The dashboard "people online" count is index-backed.
-- ════════════════════════════════════════════════════════════════
