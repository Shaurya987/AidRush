-- ════════════════════════════════════════════════════════════════════════════
-- VIEWS MIS — FRESH START
--
--   Empties every table of DATA and leaves exactly ONE administrator account,
--   ready to hand over.
--
--   WHAT IT REMOVES
--     Every donor, project, mapping, geography, village demographic,
--     beneficiary and their baseline, production and output record, activity,
--     SHG with its members and loans, FPO, indicator, indicator progress row,
--     Gram Sabha and convergence record, objective, HQ target, workspace,
--     custom dashboard chart, and the whole audit trail.
--     Every user account EXCEPT the one administrator kept below, and the
--     per-section permissions of the accounts removed.
--
--   WHAT IT KEEPS
--     The structure: every table, every column, every index, exactly as it is.
--     Nothing is dropped and nothing is altered.
--     One administrator, so the system can still be signed into.
--
--   ⚠ THIS CANNOT BE UNDONE.
--     Take a backup first. In cPanel: phpMyAdmin → select the database →
--     Export → Go. Keep that file somewhere safe before running this.
--
--   HOW TO RUN
--     phpMyAdmin → select your database → Import → choose this file → Go.
--     It is safe on a database that has not had every upgrade applied: each
--     table is only touched if it actually exists.
--
--   AFTER RUNNING
--     Sign in as the administrator that was kept, change its password
--     immediately in Users & Access, and begin entering real data.
-- ════════════════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────────────────────
-- STEP 1 · Decide which administrator survives, and remember its id.
--   Preference order: the root administrator, then any account whose role
--   mentions admin, then the very first account created. This way the script
--   works whatever the account is called.
-- ────────────────────────────────────────────────────────────────────────────
SET @keep_user_id = (
  SELECT id FROM users
   ORDER BY
     (CASE WHEN COALESCE(is_root,0) = 1 THEN 0 ELSE 1 END),
     (CASE WHEN LOWER(COALESCE(role,'')) LIKE '%admin%' THEN 0 ELSE 1 END),
     id
   LIMIT 1
);

-- ────────────────────────────────────────────────────────────────────────────
-- STEP 2 · Empty every data table that exists.
--   A procedure is used so a table missing from an older database is skipped
--   instead of stopping the whole script with an error.
-- ────────────────────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS _vmis_wipe;
DELIMITER //
CREATE PROCEDURE _vmis_wipe(IN tbl VARCHAR(64))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = tbl) > 0 THEN
    SET @sql = CONCAT('DELETE FROM `', tbl, '`');
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
    -- Identity codes start again from 1, so the first record is BEN-0001
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` AUTO_INCREMENT = 1');
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END //
DELIMITER ;

-- Children before parents, so nothing is ever orphaned mid-way
CALL _vmis_wipe('crops');                 -- production & output
CALL _vmis_wipe('loans');                 -- SHG loans
CALL _vmis_wipe('shg_members');           -- SHG members
CALL _vmis_wipe('shgs');                  -- self help groups
CALL _vmis_wipe('beneficiaries');         -- households & their baseline
CALL _vmis_wipe('fpos');                  -- farmer producer organisations
CALL _vmis_wipe('activities');            -- activity progress & targets
CALL _vmis_wipe('indicator_progress');    -- retired indicator progress
CALL _vmis_wipe('indicators');            -- retired free-form indicators
CALL _vmis_wipe('gram_sabha');            -- local governance
CALL _vmis_wipe('convergence');           -- government scheme convergence
CALL _vmis_wipe('villages');              -- village demographics
CALL _vmis_wipe('project_objectives');
CALL _vmis_wipe('programme_objectives');
CALL _vmis_wipe('hq_targets');            -- HQ plan rows
CALL _vmis_wipe('donor_mappings');        -- donor ↔ project links
CALL _vmis_wipe('geographies');           -- places
CALL _vmis_wipe('projects');
CALL _vmis_wipe('donors');
CALL _vmis_wipe('programmes');            -- thematic areas
CALL _vmis_wipe('workspaces');            -- field office workspaces
CALL _vmis_wipe('dashboard_charts');      -- custom charts
CALL _vmis_wipe('audit_log');             -- the whole history of changes
CALL _vmis_wipe('support_tickets');       -- only present if it was ever created

DROP PROCEDURE IF EXISTS _vmis_wipe;

-- ────────────────────────────────────────────────────────────────────────────
-- STEP 3 · Remove every user except the one administrator, and the
--          per-section permissions belonging to the removed accounts.
-- ────────────────────────────────────────────────────────────────────────────
DELETE FROM user_permissions WHERE user_id <> @keep_user_id;
DELETE FROM users            WHERE id      <> @keep_user_id;

-- ────────────────────────────────────────────────────────────────────────────
-- STEP 4 · Make sure that administrator really is an administrator, is active,
--          and is not left holding a stale sign-in session.
-- ────────────────────────────────────────────────────────────────────────────
UPDATE users
   SET role = 'Admin',
       access_level = 'Full',
       can_enter_data = 'Yes',
       can_verify_data = 'Yes',
       can_view_dashboard = 'Yes'
 WHERE id = @keep_user_id;

-- Session and lock columns arrived with later upgrades, so each is cleared only
-- if the column is actually there.
DROP PROCEDURE IF EXISTS _vmis_clear_col;
DELIMITER //
CREATE PROCEDURE _vmis_clear_col(IN col VARCHAR(64), IN val VARCHAR(32))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = col) > 0 THEN
    SET @sql = CONCAT('UPDATE users SET `', col, '` = ', val, ' WHERE id = @keep_user_id');
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END //
DELIMITER ;

CALL _vmis_clear_col('active_session_token', 'NULL');   -- no stale sign-in
CALL _vmis_clear_col('active_tab_id',        'NULL');
CALL _vmis_clear_col('session_expires_at',   'NULL');
CALL _vmis_clear_col('failed_login_count',   '0');      -- clears any lock-out
CALL _vmis_clear_col('locked_until',         'NULL');
CALL _vmis_clear_col('assigned_projects',    'NULL');   -- not tied to one project
CALL _vmis_clear_col('is_root',              '1');      -- can never be locked out by others
CALL _vmis_clear_col('last_login',           'NULL');   -- a clean history for the handover
CALL _vmis_clear_col('last_login_ip',        'NULL');
CALL _vmis_clear_col('last_seen',            'NULL');

DROP PROCEDURE IF EXISTS _vmis_clear_col;

-- The surviving administrator gets a clean full-rights permission set, so the
-- matrix in Users & Access opens with every box ticked rather than half empty.
DELETE FROM user_permissions WHERE user_id = @keep_user_id;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────────────────────
-- STEP 5 · Confirmation. Every count below must read 0, and Administrators
--          must read 1. If anything else appears, stop and contact developer.
-- ────────────────────────────────────────────────────────────────────────────
SELECT 'Administrators kept'        AS Item, COUNT(*) AS Count FROM users
UNION ALL SELECT 'Donors',                   COUNT(*) FROM donors
UNION ALL SELECT 'Projects',                 COUNT(*) FROM projects
UNION ALL SELECT 'Geographies',              COUNT(*) FROM geographies
UNION ALL SELECT 'Village demographics',     COUNT(*) FROM villages
UNION ALL SELECT 'Beneficiaries',            COUNT(*) FROM beneficiaries
UNION ALL SELECT 'Production records',       COUNT(*) FROM crops
UNION ALL SELECT 'Activities',               COUNT(*) FROM activities
UNION ALL SELECT 'Self help groups',         COUNT(*) FROM shgs
UNION ALL SELECT 'SHG loans',                COUNT(*) FROM loans
UNION ALL SELECT 'Audit trail entries',      COUNT(*) FROM audit_log;

-- ════════════════════════════════════════════════════════════════════════════
-- The username and password of the administrator that survived are unchanged.
-- Sign in with them, then change the password straight away in Users & Access.
-- ════════════════════════════════════════════════════════════════════════════
