# VIEWS India MIS — Full Stack (PHP + MySQL)

A live, role-based Management Information System for the VIEWS India Livelihoods Programme.
Frontend (HTML/JS) + REST API (PHP) + Database (MySQL/MariaDB). All data is real — imported
from the client Excel (2,700+ beneficiaries, 152 SHGs, 129 geographies, 42 donor mappings, etc.).

---

## What's inside
```
views-mis/
  index.html          ← the app (open this in the browser)
  api/
    config.php        ← DB credentials (EDIT THIS)
    api.php           ← REST API: auth + CRUD + dashboard + audit
  sql/
    schema.sql        ← all tables (auto-generated from the Excel)
    seed.sql          ← all real data + 4 login accounts
    _generate.py      ← (dev only) regenerates schema+seed from the Excel
  README.md
```

## Requirements
- **XAMPP** (Windows/Mac/Linux) or **MAMP** (Mac) — gives you Apache + PHP + MySQL in one click.
  Download: https://www.apachefriends.org/

---

## Setup (XAMPP — 5 steps)

1. **Copy the folder** `views-mis` into XAMPP's web root:
   - Windows: `C:\xampp\htdocs\views-mis`
   - Mac: `/Applications/XAMPP/htdocs/views-mis`

2. **Start Apache + MySQL** from the XAMPP Control Panel.

3. **Create the database & import data.** Open phpMyAdmin (http://localhost/phpmyadmin):
   - Click **New** → create a database named **`views_mis`** (collation `utf8mb4_general_ci`).
   - Select it → **Import** tab → choose `sql/schema.sql` → **Go**.
   - **Import** again → choose `sql/seed.sql` → **Go**.

   *(Command-line alternative:)*
   ```bash
   mysql -u root -p -e "CREATE DATABASE views_mis CHARACTER SET utf8mb4;"
   mysql -u root -p views_mis < sql/schema.sql
   mysql -u root -p views_mis < sql/seed.sql
   ```

4. **Check `api/config.php`** matches your MySQL login:
   - XAMPP default: user `root`, password `` (empty) — already set.
   - MAMP default: user `root`, password `root` — change `DB_PASS` to `'root'`.

5. **Open the app:** http://localhost/views-mis/index.html

---

## Login accounts
| Username | Password | Role | Can do |
|----------|----------|------|--------|
| `admin`   | `admin123`  | Administrator   | Everything (incl. users) |
| `manager` | `mgr123`    | Verifier        | Add / edit / delete all data |
| `field`   | `field123`  | Data Entry      | Add / edit / delete field data |
| `donor`   | `donor123`  | Donor Viewer    | Read-only |

(Click an account on the login screen to auto-fill & sign in.)

---

## Features
- **Live dashboard** — KPIs + 7 charts straight from the database, filterable by Programme / Project / Donor.
- **13 data modules** — Programmes, Projects, Donors, Donor Mappings, Geography, Village Demographics,
  Beneficiaries (with Religion, Ration Card), Production/Outputs (any sector), Activities & Targets,
  SHGs, SHG Loans, Indicators, Indicator Progress — full create / edit / delete via the API.
- **Role-based permissions** — enforced server-side (donor = read-only; only admin manages users).
- **Immutable audit trail** — every login, create, edit, delete is logged in the `audit_log` table.
- **Excel export** per module + full workbook, and a branded **Custom Report** (print to PDF).
- **Search & filters** on every table; donor-wise / programme-wise reports.

## Notes
- Data persists in MySQL — changes are saved for all users (true multi-user, not browser storage).
- To re-import fresh data, drop the `views_mis` database and re-run steps 3.
- Passwords are stored as bcrypt hashes; the login uses PHP sessions (cookies).
