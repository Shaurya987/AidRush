#!/usr/bin/env python3
"""Reads the VIEWS MIS Excel and emits schema.sql + seed.sql for MySQL."""
import datetime, re, sys
from openpyxl import load_workbook

XLSX = "/Users/shaurya/Downloads/MIS_Client_Data_Collection_Template_With_Explanations - Shared (1).xlsx"
OUT_DIR = "/Users/shaurya/Downloads/MIS views india/views-mis/sql"

# table -> (sheet, header_row_col_offset)
#   offset 0 = header starts at column 1; 1 = header shifted right by one (Project_Master)
TABLES = {
    "programmes":         ("Programme_Master", 0),
    "projects":           ("Project_Master", 1),   # Project_ID is col A, headers shifted
    "donors":             ("Donor_Master", 0),
    "donor_mappings":     ("Donor_Project_Mapping", 0),
    "geographies":        ("Geography_Master", 0),
    "villages":           ("Village_Demographic", 0),
    "beneficiaries":      ("Beneficiary_Farmer_Master", 0),
    "crops":              ("Crop_Production_Recording", 0),
    "activities":         ("Activity_Target_Achievement", 0),
    "shgs":               ("SHG_Master", 0),
    "loans":              ("SHG_Loan_Tracking", 0),
    "indicators":         ("Indicator_Master", 0),
    "indicator_progress": ("Indicator_Progress", 0),
}
# For projects, the real first column header (missing in sheet) is Project_ID
PROJECT_FIRST_COL = "Project_ID"

def snake(h):
    h = str(h).strip()
    h = h.replace("/", " ").replace("(", " ").replace(")", " ").replace("-", " ")
    h = re.sub(r"[^0-9A-Za-z _]", "", h)   # keep underscores
    h = re.sub(r"\s+", "_", h.strip())
    h = re.sub(r"_+", "_", h)
    return h.lower()

# EXACT column-name typing (precise, no substring guessing)
INT_COLS = {
    "age","family_members","total_hhs","hhs_covered_under_project","sc_hhs","st_hhs","obc_hhs","general_hhs",
    "no_of_members","total_target","target_year_1","target_year_2","target_year_3",
    "y1_q1","y1_q2","y1_q3","y1_q4","y2_q1","y2_q2","y2_q3","y2_q4","y3_q1","y3_q2","y3_q3","y3_q4",
    "y1_total_achievement","y2_total_achievement","y3_total_achievement",
    "y1_balance","y2_balance","y3_balance","cumulative_balance",
}
DEC_COLS = {
    "total_approved_budget_inr","approved_amount_inr","current_income_per_annum_inr","total_land_acre",
    "area_acre","total_expenditure_inr","production_kg","rate_inr_per_kg","income_inr","self_consumption_kg",
    "net_profit_inr","monthly_savings_by_members_inr","loan_amount_inr",
    "baseline_value","project_target","target_for_period","achievement_for_period","cumulative_achievement","variance",
}
DATE_COLS = {"programme_start_date","programme_end_date","project_start_date","project_end_date",
             "funding_start_date","funding_end_date","registration_date","date_of_inception",
             "loan_receiving_date"}

def coltype(col):
    if col in DEC_COLS: return "DECIMAL(16,2)"
    if col in INT_COLS: return "INT"
    if col in DATE_COLS: return "VARCHAR(40)"
    return "TEXT"

def cell_to_sql(v, ctype):
    if v is None or v == "":
        return "NULL"
    if isinstance(v, (datetime.datetime, datetime.date)):
        return "'" + v.strftime("%Y-%m-%d") + "'"
    if ctype == "INT":
        try: return str(int(float(str(v).replace(",","").strip())))
        except: return "NULL"
    if ctype.startswith("DECIMAL"):
        try: return str(round(float(str(v).replace(",","").replace("₹","").strip()),2))
        except: return "NULL"
    s = str(v).replace("\\","\\\\").replace("'", "''")
    return "'" + s + "'"

def get_headers(ws, offset):
    cols = []
    maxc = ws.max_column
    start = 1
    if offset == 1:
        cols.append(PROJECT_FIRST_COL)
        start = 2
    for c in range(start, maxc+1):
        v = ws.cell(row=1, column=c).value
        if v is None or str(v).strip()=="":
            continue
        cols.append(str(v).strip())
    return cols

def get_rows(ws, offset, ncols):
    rows = []
    # data starts at row 3 (row 2 is the explanation row)
    for r in range(3, ws.max_row+1):
        vals = [ws.cell(row=r, column=c).value for c in range(1, 1+ncols)]
        if all(v in (None,"") for v in vals):
            continue
        rows.append(vals)
    return rows

def main():
    wb = load_workbook(XLSX, data_only=True)
    schema = []
    seed = []
    schema.append("-- VIEWS India MIS — MySQL schema (auto-generated from client Excel)\n"
                  "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n")
    seed.append("-- VIEWS India MIS — seed data (auto-generated from client Excel)\n"
                "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n")

    summary = {}
    for table, (sheet, offset) in TABLES.items():
        ws = wb[sheet]
        headers = get_headers(ws, offset)
        cols = [snake(h) for h in headers]
        # de-dup column names
        seen={}; ucols=[]
        for c in cols:
            if c in seen:
                seen[c]+=1; c=f"{c}_{seen[c]}"
            else:
                seen[c]=0
            ucols.append(c)
        cols = ucols
        types = [coltype(c) for c in cols]

        # CREATE TABLE
        lines = [f"DROP TABLE IF EXISTS `{table}`;", f"CREATE TABLE `{table}` ("]
        lines.append("  `id` INT AUTO_INCREMENT PRIMARY KEY,")
        for c,t in zip(cols, types):
            lines.append(f"  `{c}` {t},")
        lines.append("  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,")
        lines.append("  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,")
        # indexes on business-key columns for fast joins/filters (first 100 chars of TEXT)
        idxcols=[c for c in cols if c.endswith("_id")]
        keylines=[f"  KEY `idx_{table}_{c}` (`{c}`(64))" for c in idxcols]
        if keylines:
            lines.append(",\n".join(keylines))
        else:
            lines[-1]=lines[-1].rstrip(",")
        lines.append(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;")
        schema.append("\n".join(lines)+"\n")

        # INSERTS
        rows = get_rows(ws, offset, len(cols))
        summary[table]=len(rows)
        if rows:
            collist = ",".join(f"`{c}`" for c in cols)
            seed.append(f"-- {table}: {len(rows)} rows")
            CHUNK=200
            for i in range(0, len(rows), CHUNK):
                chunk = rows[i:i+CHUNK]
                seed.append(f"INSERT INTO `{table}` ({collist}) VALUES")
                vlines=[]
                for vals in chunk:
                    cells = ",".join(cell_to_sql(v, t) for v,t in zip(vals, types))
                    vlines.append(f"({cells})")
                seed.append(",\n".join(vlines)+";")
            seed.append("")

    # users table (auth) — built separately
    schema.append("""DROP TABLE IF EXISTS `users`;
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
""")

    # audit_log table
    schema.append("""DROP TABLE IF EXISTS `audit_log`;
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
""")

    # dashboard_charts — user-defined charts (flexible: pick any indicator, any chart type)
    schema.append("""DROP TABLE IF EXISTS `dashboard_charts`;
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
""")

    # programme_objectives — a programme can have MANY objectives (Excel had 1 programme, many rows)
    schema.append("""DROP TABLE IF EXISTS `programme_objectives`;
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
""")
    schema.append("SET FOREIGN_KEY_CHECKS=1;\n")

    # default login users (password = bcrypt). We'll write plain markers and let PHP hash on install,
    # but simplest: precomputed bcrypt for the 4 demo passwords.
    import bcrypt
    def h(pw): return bcrypt.hashpw(pw.encode(), bcrypt.gensalt()).decode()
    ah,mh,fh,dh = h("admin123"),h("mgr123"),h("field123"),h("donor123")
    seed.append(f"""-- Default login accounts. Passwords: admin/admin123, manager/mgr123, field/field123, donor/donor123
INSERT INTO `users` (username,password_hash,user_name,designation,organization,role,email,phone,access_level,programme_access,project_access,donor_access,can_enter_data,can_verify_data,can_view_dashboard,remarks) VALUES
('admin','{ah}','Saroj Kumar Satapathy','Programme Director','VIEWS India','Admin','admin@viewsindia.org','9876500001','Full','All','All','All','Yes','Yes','Yes','Full system access'),
('manager','{mh}','Programme Manager','M&E Manager','VIEWS India','Verifier','manager@viewsindia.org','9876500002','Programme','All','All','All','Yes','Yes','Yes','Verifies field entries'),
('field','{fh}','Field Coordinator','Block Coordinator','VIEWS India','Data Entry','field@viewsindia.org','9876500003','Project','All','All','All','Yes','No','No','Field-level entry'),
('donor','{dh}','Donor Viewer','CSR Manager','Donor Org','Donor Viewer','donor@viewsindia.org','9876500004','Donor','All','All','All','No','No','Yes','External donor view');
""")

    # ---- Sample M&E indicators + quarterly progress (powers the custom dashboard charts) ----
    seed.append("""-- Sample indicators for the custom dashboard-chart builder (editable / deletable)
INSERT INTO `indicators` (indicator_id,programme_id,project_id,donor_id,indicator_name,indicator_type,unit,baseline_value,project_target,data_source,reporting_frequency,remarks) VALUES
('IND-S01','PRG-LIV-001','PRJ-LIV-FARM-001','DON-CSR-001','Farmers adopting improved paddy (SRI)','Output','Number',0,1700,'HH Tracker','Quarterly','Sample indicator'),
('IND-S02','PRG-LIV-001','PRJ-LIV-FARM-001','DON-LOC-001','Acres under millet cultivation','Output','Acres',50,1500,'Field survey','Quarterly','Sample indicator'),
('IND-S03','PRG-LIV-001','PRJ-LIV-FARM-001','DON-CSR-001','Households with nutrition gardens','Output','Number',120,2100,'Survey','Quarterly','Sample indicator'),
('IND-S04','PRG-LIV-001','PRJ-LIV-FARM-001','DON-LOC-001','SHGs with bank linkage','Output','Number',8,200,'Bank records','Quarterly','Sample indicator'),
('IND-S05','PRG-LIV-001','PRJ-LIV-FARM-001','DON-CSR-001','Average household income increase','Outcome','%',0,35,'Annual survey','Annual','Sample indicator'),
('IND-S06','PRG-LIV-001','PRJ-LIV-FARM-001','DON-LOC-001','Women trained in livelihood skills','Output','Number',0,2000,'Training records','Quarterly','Sample indicator');
""")
    prog=[]
    plan={'IND-S01':[300,650,980,1684],'IND-S02':[200,520,845,1100],'IND-S03':[600,1200,1800,2100],
          'IND-S04':[15,40,58,72],'IND-S05':[5,9,12,14],'IND-S06':[120,300,520,640]}
    qtgt={'IND-S01':425,'IND-S02':375,'IND-S03':525,'IND-S04':50,'IND-S05':9,'IND-S06':500}
    rid=1
    rows=[]
    for ind,vals in plan.items():
        cum=0
        for i,v in enumerate(vals):
            cum+=v; q=f"Q{i+1}"
            rows.append(f"('PROG-S{rid:03d}','{ind}','PRG-LIV-001','PRJ-LIV-FARM-001','','{q} 2024-25','2024-25','{q}',{qtgt[ind]},{v},{cum},{v-qtgt[ind]},'MIS','Sample progress')")
            rid+=1
    seed.append("INSERT INTO `indicator_progress` (progress_id,indicator_id,programme_id,project_id,donor_id,reporting_period,reporting_year,quarter,target_for_period,achievement_for_period,cumulative_achievement,variance,verification_source,remarks) VALUES\n"+",\n".join(rows)+";\n")

    # ---- Two starter dashboard charts ----
    seed.append("""INSERT INTO `dashboard_charts` (title,source,indicator_id,chart_type,metric,created_by,sort_order) VALUES
('SRI Paddy Adoption — Quarterly Progress','indicator','IND-S01','line','progress','admin',1),
('SHG Bank Linkage — Target vs Achievement','indicator','IND-S04','bar','progress','admin',2);
""")

    # ---- Seed programme objectives from the programme rows (each programme can have many) ----
    seed.append("""INSERT INTO `programme_objectives` (programme_id, objective, status)
SELECT programme_id, programme_objective, programme_status FROM programmes
WHERE programme_objective IS NOT NULL AND programme_objective <> '';
""")

    seed.append("SET FOREIGN_KEY_CHECKS=1;\n")

    with open(OUT_DIR+"/schema.sql","w") as f: f.write("\n".join(schema))
    with open(OUT_DIR+"/seed.sql","w") as f: f.write("\n".join(seed))
    print("Wrote schema.sql and seed.sql")
    for t,n in summary.items(): print(f"  {t}: {n} rows")

if __name__=="__main__":
    main()
