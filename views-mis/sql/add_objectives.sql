-- ════════════════════════════════════════════════════════════════
-- MIGRATION for the EXISTING live database (run once in phpMyAdmin).
-- Adds the "multiple objectives per programme" feature WITHOUT touching
-- your existing data. Select your database first, then Import this file
-- (or paste into the SQL tab and Go).
-- ════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `programme_objectives` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `objective_id` VARCHAR(40),
  `programme_id` VARCHAR(120),
  `objective` TEXT,
  `status` VARCHAR(40) DEFAULT 'Ongoing',
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_obj_prg` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed objectives from the current programme rows, attaching each to the
-- CANONICAL programme of its name (the lowest id sharing that name), so all
-- objectives sit under the real 2 programmes — only if not already seeded.
INSERT INTO `programme_objectives` (programme_id, objective, status)
SELECT c.programme_id, p.programme_objective, p.programme_status
FROM programmes p
JOIN (SELECT programme_name, MIN(id) cid FROM programmes GROUP BY programme_name) g
  ON g.programme_name = p.programme_name
JOIN programmes c ON c.id = g.cid
WHERE p.programme_objective IS NOT NULL AND p.programme_objective <> ''
  AND NOT EXISTS (SELECT 1 FROM programme_objectives);

-- ────────────────────────────────────────────────────────────────
-- OPTIONAL CLEANUP (only if you want EXACTLY one row per programme name).
-- This deletes duplicate programme rows, keeping the canonical (lowest id).
-- Objectives were already moved above, so they are preserved.
-- Review before running. Uncomment the 3 lines to apply.
-- ────────────────────────────────────────────────────────────────
-- DELETE p FROM programmes p
-- JOIN (SELECT programme_name, MIN(id) cid FROM programmes GROUP BY programme_name) g
--   ON g.programme_name = p.programme_name
-- WHERE p.id <> g.cid;
