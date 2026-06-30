-- VIEWS India MIS — MySQL schema (auto-generated from client Excel)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `programmes`;
CREATE TABLE `programmes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `programme_id` TEXT,
  `programme_name` TEXT,
  `programme_objective` TEXT,
  `thematic_area` TEXT,
  `programme_start_date` VARCHAR(40),
  `programme_end_date` VARCHAR(40),
  `programme_status` TEXT,
  `programme_manager` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_programmes_programme_id` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` TEXT,
  `programme_id` TEXT,
  `project_name` TEXT,
  `project_code` TEXT,
  `project_objective` TEXT,
  `project_start_date` VARCHAR(40),
  `project_end_date` VARCHAR(40),
  `project_status` TEXT,
  `project_geography_summary` TEXT,
  `target_beneficiaries` TEXT,
  `implementation_partner` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_projects_project_id` (`project_id`(64)),
  KEY `idx_projects_programme_id` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `donors`;
CREATE TABLE `donors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `donor_id` TEXT,
  `donor_name` TEXT,
  `donor_type` TEXT,
  `contact_person` TEXT,
  `designation` TEXT,
  `email` TEXT,
  `phone_number` TEXT,
  `funding_start_date` VARCHAR(40),
  `funding_end_date` VARCHAR(40),
  `total_approved_budget_inr` DECIMAL(16,2),
  `reporting_frequency` TEXT,
  `dashboard_access_required` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_donors_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `donor_mappings`;
CREATE TABLE `donor_mappings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mapping_id` TEXT,
  `donor_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_funded_component` TEXT,
  `funded_activity` TEXT,
  `budget_head` TEXT,
  `approved_amount_inr` DECIMAL(16,2),
  `donor_specific_indicator` TEXT,
  `reporting_requirement` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_donor_mappings_mapping_id` (`mapping_id`(64)),
  KEY `idx_donor_mappings_donor_id` (`donor_id`(64)),
  KEY `idx_donor_mappings_programme_id` (`programme_id`(64)),
  KEY `idx_donor_mappings_project_id` (`project_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `geographies`;
CREATE TABLE `geographies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `geography_id` TEXT,
  `country` TEXT,
  `state` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village_or_ward` TEXT,
  `project_id` TEXT,
  `programme_id` TEXT,
  `responsible_staff` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_geographies_geography_id` (`geography_id`(64)),
  KEY `idx_geographies_project_id` (`project_id`(64)),
  KEY `idx_geographies_programme_id` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `villages`;
CREATE TABLE `villages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `demographic_id` TEXT,
  `geography_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village` TEXT,
  `total_hhs` INT,
  `hhs_covered_under_project` INT,
  `sc_hhs` INT,
  `st_hhs` INT,
  `obc_hhs` INT,
  `general_hhs` INT,
  `village_contact_person` TEXT,
  `village_contact_person_cell_no` TEXT,
  `responsible_staff` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_villages_demographic_id` (`demographic_id`(64)),
  KEY `idx_villages_geography_id` (`geography_id`(64)),
  KEY `idx_villages_programme_id` (`programme_id`(64)),
  KEY `idx_villages_project_id` (`project_id`(64)),
  KEY `idx_villages_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `beneficiaries`;
CREATE TABLE `beneficiaries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `beneficiary_id` TEXT,
  `registration_date` VARCHAR(40),
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `geography_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village` TEXT,
  `beneficiary_farmer_name` TEXT,
  `father_or_spouse_name` TEXT,
  `age` INT,
  `gender` TEXT,
  `caste` TEXT,
  `religion` TEXT,
  `ration_card` TEXT,
  `contact_number` TEXT,
  `family_members` INT,
  `primary_income_source` TEXT,
  `secondary_income_source` TEXT,
  `current_income_per_annum_inr` DECIMAL(16,2),
  `bl_paddy_area` DECIMAL(16,2),
  `bl_paddy_income` DECIMAL(16,2),
  `bl_millet_area` DECIMAL(16,2),
  `bl_millet_income` DECIMAL(16,2),
  `bl_vegetable_area` DECIMAL(16,2),
  `bl_vegetable_income` DECIMAL(16,2),
  `bl_mushroom_income` DECIMAL(16,2),
  `bl_goat_income` DECIMAL(16,2),
  `bl_poultry_income` DECIMAL(16,2),
  `bl_micro_enterprise_income` DECIMAL(16,2),
  `total_land_acre` DECIMAL(16,2),
  `source_of_irrigation` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_beneficiaries_beneficiary_id` (`beneficiary_id`(64)),
  KEY `idx_beneficiaries_programme_id` (`programme_id`(64)),
  KEY `idx_beneficiaries_project_id` (`project_id`(64)),
  KEY `idx_beneficiaries_donor_id` (`donor_id`(64)),
  KEY `idx_beneficiaries_geography_id` (`geography_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `crops`;
CREATE TABLE `crops` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `production_record_id` TEXT,
  `beneficiary_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `geography_id` TEXT,
  `crop` TEXT,
  `alternative_livelihood` TEXT,
  `project_year` TEXT,
  `season` TEXT,
  `area_acre` DECIMAL(16,2),
  `total_expenditure_inr` DECIMAL(16,2),
  `production_kg` DECIMAL(16,2),
  `rate_inr_per_kg` DECIMAL(16,2),
  `income_inr` DECIMAL(16,2),
  `self_consumption_kg` DECIMAL(16,2),
  `net_profit_inr` DECIMAL(16,2),
  `data_source` TEXT,
  `evidence_link` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_crops_production_record_id` (`production_record_id`(64)),
  KEY `idx_crops_beneficiary_id` (`beneficiary_id`(64)),
  KEY `idx_crops_programme_id` (`programme_id`(64)),
  KEY `idx_crops_project_id` (`project_id`(64)),
  KEY `idx_crops_donor_id` (`donor_id`(64)),
  KEY `idx_crops_geography_id` (`geography_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `project_activity` TEXT,
  `unit_of_measurement` TEXT,
  `total_target` INT,
  `target_year_1` INT,
  `y1_q1` INT,
  `y1_q2` INT,
  `y1_q3` INT,
  `y1_q4` INT,
  `y1_total_achievement` INT,
  `y1_balance` INT,
  `y1_remarks` TEXT,
  `target_year_2` INT,
  `y2_q1` INT,
  `y2_q2` INT,
  `y2_q3` INT,
  `y2_q4` INT,
  `y2_total_achievement` INT,
  `y2_balance` INT,
  `y2_remarks` TEXT,
  `target_year_3` INT,
  `y3_q1` INT,
  `y3_q2` INT,
  `y3_q3` INT,
  `y3_q4` INT,
  `y3_total_achievement` INT,
  `y3_balance` INT,
  `y3_remarks` TEXT,
  `cumulative_achievement` DECIMAL(16,2),
  `cumulative_balance` INT,
  `means_of_verification` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_activities_activity_id` (`activity_id`(64)),
  KEY `idx_activities_programme_id` (`programme_id`(64)),
  KEY `idx_activities_project_id` (`project_id`(64)),
  KEY `idx_activities_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `shgs`;
CREATE TABLE `shgs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shg_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `geography_id` TEXT,
  `district` TEXT,
  `block` TEXT,
  `gram_panchayat` TEXT,
  `village` TEXT,
  `shg_name` TEXT,
  `date_of_inception` VARCHAR(40),
  `no_of_members` INT,
  `monthly_savings_by_members_inr` DECIMAL(16,2),
  `bank_account_no` TEXT,
  `bank_name` TEXT,
  `branch` TEXT,
  `ifsc_code` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_shgs_shg_id` (`shg_id`(64)),
  KEY `idx_shgs_programme_id` (`programme_id`(64)),
  KEY `idx_shgs_project_id` (`project_id`(64)),
  KEY `idx_shgs_donor_id` (`donor_id`(64)),
  KEY `idx_shgs_geography_id` (`geography_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `loans`;
CREATE TABLE `loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_record_id` TEXT,
  `shg_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `financial_year` TEXT,
  `loan_receiving_date` VARCHAR(40),
  `loan_amount_inr` DECIMAL(16,2),
  `loan_source` TEXT,
  `purpose_of_loan` TEXT,
  `repayment_status` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_loans_loan_record_id` (`loan_record_id`(64)),
  KEY `idx_loans_shg_id` (`shg_id`(64)),
  KEY `idx_loans_programme_id` (`programme_id`(64)),
  KEY `idx_loans_project_id` (`project_id`(64)),
  KEY `idx_loans_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `indicators`;
CREATE TABLE `indicators` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `indicator_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `indicator_name` TEXT,
  `indicator_type` TEXT,
  `unit` TEXT,
  `baseline_value` DECIMAL(16,2),
  `project_target` DECIMAL(16,2),
  `data_source` TEXT,
  `reporting_frequency` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_indicators_indicator_id` (`indicator_id`(64)),
  KEY `idx_indicators_programme_id` (`programme_id`(64)),
  KEY `idx_indicators_project_id` (`project_id`(64)),
  KEY `idx_indicators_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `indicator_progress`;
CREATE TABLE `indicator_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `progress_id` TEXT,
  `indicator_id` TEXT,
  `programme_id` TEXT,
  `project_id` TEXT,
  `donor_id` TEXT,
  `reporting_period` TEXT,
  `reporting_year` TEXT,
  `quarter` TEXT,
  `target_for_period` DECIMAL(16,2),
  `achievement_for_period` DECIMAL(16,2),
  `cumulative_achievement` DECIMAL(16,2),
  `variance` DECIMAL(16,2),
  `verification_source` TEXT,
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_indicator_progress_progress_id` (`progress_id`(64)),
  KEY `idx_indicator_progress_indicator_id` (`indicator_id`(64)),
  KEY `idx_indicator_progress_programme_id` (`programme_id`(64)),
  KEY `idx_indicator_progress_project_id` (`project_id`(64)),
  KEY `idx_indicator_progress_donor_id` (`donor_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_name` VARCHAR(120),
  `designation` VARCHAR(120),
  `organization` VARCHAR(120),
  `role` VARCHAR(60),
  `email` VARCHAR(160),
  `phone` VARCHAR(40),
  `access_level` VARCHAR(60),
  `programme_access` VARCHAR(120),
  `project_access` VARCHAR(120),
  `donor_access` VARCHAR(120),
  `can_enter_data` VARCHAR(8) DEFAULT 'No',
  `can_verify_data` VARCHAR(8) DEFAULT 'No',
  `can_view_dashboard` VARCHAR(8) DEFAULT 'Yes',
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60),
  `role` VARCHAR(60),
  `action` VARCHAR(20),
  `entity` VARCHAR(60),
  `entity_id` VARCHAR(120),
  `message` TEXT,
  `ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `dashboard_charts`;
CREATE TABLE `dashboard_charts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `source` VARCHAR(40) DEFAULT 'indicator',   -- 'indicator' OR a table key (beneficiaries, crops, ...)
  `indicator_id` VARCHAR(120),                -- used only when source='indicator'
  `group_by` VARCHAR(64),                     -- pivot dimension column (e.g. caste, block, donor_id)
  `measure` VARCHAR(64) DEFAULT 'count',      -- 'count' OR a numeric column to SUM
  `chart_type` VARCHAR(20) DEFAULT 'bar',     -- bar | line | doughnut | pie
  `metric` VARCHAR(40) DEFAULT 'progress',
  `created_by` VARCHAR(60),
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `programme_objectives`;
CREATE TABLE `programme_objectives` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `objective_id` VARCHAR(40),
  `programme_id` VARCHAR(120),
  `objective` TEXT,
  `status` VARCHAR(40) DEFAULT 'Ongoing',
  `remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_obj_prg` (`programme_id`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
