-- ════════════════════════════════════════════════════════════════
-- OPTIONAL data reconciliation (run once in phpMyAdmin → SQL tab).
-- Fixes typos in the source Excel so EVERY programme/donor reference
-- shows a NAME instead of a raw ID. Review before running.
-- ════════════════════════════════════════════════════════════════

-- 1) Programme-ID typo: 600 beneficiaries reference 'PRG-LIV-002',
--    but the programme was entered as 'PGR-LIV-002' (PGR vs PRG).
UPDATE `programmes` SET `programme_id`='PRG-LIV-002' WHERE `programme_id`='PGR-LIV-002';

-- 2) Missing donor 'DON-FC-002' is referenced by 600 beneficiaries but
--    was never added to the Donors table. Add it (then rename it in the
--    Donors page to the real funder name).
INSERT INTO `donors` (`donor_id`,`donor_name`,`donor_type`)
SELECT 'DON-FC-002','Funding Partner (please rename)','Other'
WHERE NOT EXISTS (SELECT 1 FROM `donors` WHERE `donor_id`='DON-FC-002');

-- After running this, reload the app — beneficiary rows will show the
-- programme & donor NAMES everywhere.
