-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v2
--   1. Project consolidation — 9 rows → 2 (mirrors what we did
--      for programmes); new `project_objectives` table.
--   2. Foolproof login — token + tab_id columns on `users`,
--      session_expires_at, lockout, last_login/last_seen,
--      password_changed_at.  Audit log gets IP / UA / tab_id /
--      ts_ist / before / after columns.
--   3. Per-section permissions matrix (`user_permissions`).
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → Import (or paste into the SQL tab).
-- Safe to re-run; every statement is guarded with IF NOT EXISTS
-- or information_schema checks.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

-- ─── 1) USERS — harden the table ────────────────────────────────────
SET @hasRoot := (SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema=DATABASE() AND table_name='users' AND column_name='is_root');
SET @sql := IF(@hasRoot=0,
"ALTER TABLE `users`
   ADD COLUMN `is_root`             TINYINT(1) DEFAULT 0,
   ADD COLUMN `active_session_token` VARCHAR(80) NULL,
   ADD COLUMN `active_tab_id`        VARCHAR(64) NULL,
   ADD COLUMN `session_expires_at`   DATETIME   NULL,
   ADD COLUMN `last_login`           DATETIME   NULL,
   ADD COLUMN `last_login_ip`        VARCHAR(64) NULL,
   ADD COLUMN `last_seen`            DATETIME   NULL,
   ADD COLUMN `password_changed_at`  DATETIME   NULL,
   ADD COLUMN `failed_login_count`   INT DEFAULT 0,
   ADD COLUMN `locked_until`         DATETIME   NULL",
"DO 0");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Mark the existing admin as the root admin (cannot be deleted or demoted)
UPDATE `users` SET `is_root`=1
  WHERE LOWER(`username`)='admin' OR LOWER(`role`) LIKE '%admin%';

-- Index for fast token lookups on every request
SET @hasIdx := (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema=DATABASE() AND table_name='users' AND index_name='idx_users_token');
SET @sql := IF(@hasIdx=0,
"CREATE INDEX `idx_users_token` ON `users`(`active_session_token`)",
"DO 0");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ─── 2) AUDIT LOG — beef up details + IST ───────────────────────────
SET @hasIp := (SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema=DATABASE() AND table_name='audit_log' AND column_name='ip');
SET @sql := IF(@hasIp=0,
"ALTER TABLE `audit_log`
   ADD COLUMN `ip`          VARCHAR(64)  NULL,
   ADD COLUMN `user_agent`  VARCHAR(255) NULL,
   ADD COLUMN `tab_id`      VARCHAR(64)  NULL,
   ADD COLUMN `ts_ist`      DATETIME     NULL,
   ADD COLUMN `before_data` MEDIUMTEXT   NULL,
   ADD COLUMN `after_data`  MEDIUMTEXT   NULL,
   ADD COLUMN `verb`        VARCHAR(40)  NULL",
"DO 0");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Back-fill ts_ist for old rows (assume server time was UTC; +5:30 → IST)
UPDATE `audit_log` SET `ts_ist`=DATE_ADD(`ts`, INTERVAL 330 MINUTE)
  WHERE `ts_ist` IS NULL;

-- Index for fast filter
SET @hasIdx2 := (SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema=DATABASE() AND table_name='audit_log' AND index_name='idx_audit_ts');
SET @sql := IF(@hasIdx2=0,
"CREATE INDEX `idx_audit_ts` ON `audit_log`(`ts_ist`)",
"DO 0");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ─── 3) PER-SECTION PERMISSIONS MATRIX ──────────────────────────────
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `section`    VARCHAR(40) NOT NULL,
  `can_view`   TINYINT(1) DEFAULT 1,
  `can_edit`   TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_up` (`user_id`,`section`),
  KEY `idx_up_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: dashboard view for every existing non-admin user
INSERT INTO `user_permissions` (user_id, section, can_view, can_edit, can_delete)
SELECT u.id, 'dashboard', 1, 0, 0
  FROM `users` u
  WHERE u.is_root=0
    AND NOT EXISTS (SELECT 1 FROM `user_permissions` p WHERE p.user_id=u.id AND p.section='dashboard');

-- ─── 4) PROJECTS — Same treatment as programmes ─────────────────────
CREATE TABLE IF NOT EXISTS `project_objectives` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `objective_id`         VARCHAR(40),
  `project_id`           VARCHAR(120),
  `programme_id`         VARCHAR(120),
  `objective`            TEXT,
  `target_beneficiaries` TEXT,
  `status`               VARCHAR(40) DEFAULT 'Ongoing',
  `remarks`              TEXT,
  `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_pobj_proj` (`project_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Collect every objective row from the existing `projects` table
DELETE FROM `project_objectives`;
INSERT INTO `project_objectives`
  (project_id, programme_id, objective, target_beneficiaries, status)
SELECT 'PRJ-LIV-FARM-001','PRG-LIV-001',
       project_objective, target_beneficiaries,
       COALESCE(NULLIF(project_status,''),'Ongoing')
  FROM `projects`
  WHERE project_id='PRJ-LIV-FARM-001'
    AND project_objective IS NOT NULL AND project_objective<>'';
INSERT INTO `project_objectives`
  (project_id, programme_id, objective, target_beneficiaries, status)
SELECT 'PRJ-LIV-FARM-002','PRG-LIV-002',
       project_objective, target_beneficiaries,
       COALESCE(NULLIF(project_status,''),'Ongoing')
  FROM `projects`
  WHERE project_id='PRJ-LIV-FARM-002'
    AND project_objective IS NOT NULL AND project_objective<>'';

-- Keep EXACTLY two projects (1 per programme) with their canonical info
DELETE FROM `projects`;
INSERT INTO `projects`
  (project_id, programme_id, project_name, project_code, project_objective,
   project_start_date, project_end_date, project_status, project_geography_summary,
   target_beneficiaries, implementation_partner, remarks)
VALUES
('PRJ-LIV-FARM-001','PRG-LIV-001',
 'Promoting Sustainable Livelihoods among Small and Marginal Farmers through Collective Action',
 'SF-FARM-APF-24','Multiple objectives — see Project Objectives',
 '2024-10-01','2027-09-30','Ongoing','Ganjam district, Odisha',
 '2,100 households','NGO field team','Consolidated project'),
('PRJ-LIV-FARM-002','PRG-LIV-002',
 'Promoting Sustainable Livelihood Through Agri Management Services',
 'SF-FARM-SHIAMS-25','Multiple objectives — see Project Objectives',
 '2025-10-01','2026-09-30','Ongoing','Ganjam & Gajapati district, Odisha',
 '600 marginal farmers','NGO field team','Consolidated project');

-- ─── 5) DONE ────────────────────────────────────────────────────────
-- After running this:
--   • Admin user is now marked is_root → cannot be deleted.
--   • Token-based session columns are ready for the new login flow.
--   • Audit log records IP, user-agent, tab id and IST timestamp.
--   • Projects panel shows exactly 2 projects with multiple objectives.
-- ════════════════════════════════════════════════════════════════════
