-- ════════════════════════════════════════════════════════════════
-- VIEWS MIS — UPGRADE v9
--   MIS STATUS — server-side field workspaces.
--   Until now each officer's workspaces lived only in their own
--   browser (localStorage). This table moves them to the database so:
--     • ADMINS see every officer's workspaces live in the new
--       "MIS Status" section (above Users & Access), and can create /
--       assign / remove workspaces for anyone.
--     • OFFICERS get their workspaces on ANY device they log in from,
--       and workspaces assigned by HQ appear automatically.
--
--   ADD-ONLY: creates ONE new table, touches nothing else.
--   Idempotent — safe to run as many times as you like.
-- ════════════════════════════════════════════════════════════════
-- Run ONCE in phpMyAdmin → select your database → Import this file.
-- ════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `workspaces` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` VARCHAR(64),
  `username` VARCHAR(190),          -- the OWNER (field officer) — matches users.username
  `name` VARCHAR(190),
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `geography_id` TEXT,
  `village` TEXT,
  `is_active` TINYINT(1) DEFAULT 0, -- the workspace this officer is currently working in
  `assigned_by` VARCHAR(190),       -- set when HQ/admin assigned it (blank = self-created)
  `created_by` VARCHAR(190),
  `updated_by` VARCHAR(190),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ws_username` (`username`),
  KEY `idx_ws_wsid` (`workspace_id`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════
-- After running this:
--   • A new admin-only "MIS Status" section appears above Users &
--     Access: live view of every officer's workspaces (who is active
--     where), plus an "Assign a workspace" form.
--   • Officers' existing browser workspaces upload themselves the
--     next time they open Data Entry — nothing is lost.
--   • Workspaces assigned by HQ appear in the officer's Field-Office
--     Entry list automatically (WS-0001, WS-0002, … IDs).
-- ════════════════════════════════════════════════════════════════
