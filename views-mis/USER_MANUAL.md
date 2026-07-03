# VIEWS India MIS — Complete User Manual

*Realtime monitoring & evaluation platform for livelihoods programmes.*
*This manual describes every section, every button, and every automatic behaviour of the system.*

---

## Table of contents

1. [Logging in & sessions](#1-logging-in--sessions)
2. [The screen layout](#2-the-screen-layout)
3. [Dashboard](#3-dashboard)
4. [Reports & Analytics](#4-reports--analytics)
5. [Data Entry — the two tiers](#5-data-entry--the-two-tiers)
   - 5.1 [HQ Setup & Targets](#51-hq-setup--targets)
   - 5.2 [Field-Office Entry & Workspaces](#52-field-office-entry--workspaces)
   - 5.3 [Importing data from Excel](#53-importing-data-from-excel)
6. [How every form behaves (shared features)](#6-how-every-form-behaves-shared-features)
7. [Master Data sections](#7-master-data-sections)
8. [Field Data sections](#8-field-data-sections)
9. [SHG & Finance sections](#9-shg--finance-sections)
10. [Monitoring & Evaluation (MNE)](#10-monitoring--evaluation-mne)
11. [MIS Status (admin)](#11-mis-status-admin)
12. [Users & Access (admin)](#12-users--access-admin)
13. [Audit Trail](#13-audit-trail)
14. [Exporting to Excel — every option](#14-exporting-to-excel--every-option)
15. [The donor↔project linkage (why "Unmapped" matters)](#15-the-donorproject-linkage)
16. [Automatic protections against bad data](#16-automatic-protections-against-bad-data)
17. [Deployment & maintenance](#17-deployment--maintenance)
18. [Troubleshooting — what each message means](#18-troubleshooting)

---

## 1. Logging in & sessions

- Open the site → enter your **username (or email) and password** → **Sign in**.
- **Wrong password too many times?** The account locks for a few minutes automatically (brute-force protection). Wait and try again, or ask an admin to reset your password.
- **One session per person.** Signing in on a second device/tab signs the first one out — this prevents shared logins.
- **Idle expiry.** After a long period of inactivity you are signed out automatically; just sign in again.
- Closing the tab signs you out and writes a "logout" line in the Audit Trail.

---

## 2. The screen layout

- **Left sidebar** — navigation. You only see the sections your permissions allow. Groups:
  - *Overview*: Dashboard · Reports & Analytics · Data Entry
  - *Master Data*: Donor Mappings · Donors · Thematic Areas · Projects · Geography · Village Demographics
  - *Field Data*: Beneficiaries · Production & Output Progress · Activities & Targets
  - *SHG & Finance*: Self-Help Groups · SHG Loans
  - *Monitoring and Evaluation (MNE)*: Indicators · Indicator Progress
  - *Admin*: MIS Status · Users & Access · Audit Trail
- **Top-right of most pages** — a live record count pill, an **⬇ Export ▾** menu, and a **+ New** button (only if you may add records).
- **Live refresh.** The dashboard silently refreshes every ~25 seconds; charts update in place with no flicker.

---

## 3. Dashboard

The at-a-glance state of the whole programme. Everything on it reacts to the filter bar.

**Filter bar** — *Thematic Area* and *Project* dropdowns (the Project list narrows when a Thematic Area is picked). Active filters appear as removable chips. **✕ Clear** resets.

**KPI cards (top row, in order):**
| Card | Meaning |
|---|---|
| Thematic Areas | Number of thematic areas (programmes) |
| Active Donors | Donors that actually fund at least one mapped project |
| Projects | Implementation projects |
| Districts / Blocks / Villages | Geographic coverage counts |
| Beneficiaries | Registered beneficiaries |
| SHGs | Self-help groups |
| Activities | Activity lines being tracked |
| Avg. Income Change | Average household income change (baseline → current) |

**Charts** (all filter-aware):
- **Beneficiaries by District & Block** — combined geography bar chart.
- **Social Order / Gender / Religion** — demographic doughnuts.
- **Household Income — Before vs After** — grouped chart with a **Bar / Line toggle** button; shows baseline vs current income and the income trend by year.
- **Top Activity Performance** — targets vs cumulative achievement with % (2 decimals).
- **Data Completion Status** — how completely each section's key fields are filled.
- **Objectives panels** — thematic-area and project objectives with status, filtered like everything else.

**Export ▾** (top right) — download the dashboard as a formatted Excel (KPIs, top activities, objectives, SHG membership etc.) or print it.

---

## 4. Reports & Analytics

One page that answers "give me a report by ___".

**The report bar:**
1. **Report:** pick the dimension — *Donor-wise, Thematic Area-wise, Project-wise, District-wise, Block-wise, Village/Ward-wise, SHG-wise*.
2. **Second dropdown** — pick the specific donor/project/district/… or *All*.
3. **⏱ Duration:** *All time / Monthly / Quarterly / Annually / Custom range…* — Custom shows **From / To** calendars. Monthly/Quarterly/Annually auto-compute the current month, quarter, or Indian financial year. The window scopes beneficiary figures by registration date.
4. **⬇ Download this report (Excel)** — one click builds a complete multi-sheet workbook for exactly the dimension + filter + duration you chose: Summary, Activity performance (quarterly), Indicator Progress, demographic & geographic breakdowns, Outputs, SHG summary, Loans by financial year, Donor budget. Performance % cells are traffic-light coloured (green ≥ 80, amber 40–79, red < 40).

**Stat strip** — Districts · Blocks · Villages · Beneficiaries · SHGs for the current filter.

**Report body** — the on-screen version of the same report, updating live as you change the filters.

**One-click Donor Report builder** (card below) — pick a donor (and optionally period), press generate: a 20+ sheet, donor-ready workbook (executive summary, donor profile, funded projects, thematic/project masters, beneficiary analysis, income impact, activities, indicators, SHGs, loans, budget utilisation, glossary).

**Master Workbook** — every section of the database as one formatted Excel file. This is also your one-click **full data backup**.

**🖨 Print / PDF** — a print-optimised version of the page for sharing.

---

## 5. Data Entry — the two tiers

> **The idea:** HQ plans the work and sets targets. Field officers enter actual data against that plan. The two tabs at the top switch between the tiers — each tab is a separate permission, so field staff can be limited to Field-Office Entry only.

### 5.1 HQ Setup & Targets

*(Default tab for HQ staff.)*

1. **Project** — pick the project. **Donor and Thematic Area fill in automatically** (from the donor↔project mapping — see §15). Only donor-mapped projects appear; a note tells you if any are hidden.
2. **District row** — pick the District, then type the planned targets: *Total Blocks, Total Villages, Total Beneficiaries*.
3. **Assigned to (officer)** — choose the field officer responsible. This is what makes the work appear in *their* "My assignments" panel.
4. **Activities · Indicators · Output** — these are not typed as numbers here; each **＋ Add … & target** button opens the real form (Activity / Indicator / Production) where the target lives on the record itself.
5. **💾 Save HQ Targets** — saves the district row (one district per save; the form clears for the next district — one donor can fund many districts).
6. **Existing HQ Targets** — table of every planned row (Donor · Thematic · Project · District · targets · Assigned to) with delete buttons.
7. **＋ New project / donor / thematic area / district** small buttons — open the real master form and bring you back with your work intact.

### 5.2 Field-Office Entry & Workspaces

*(What field officers see. Their tab may be the only one visible.)*

**Workspaces — the heart of field entry.** A workspace is a saved context: *Project + District + Block + GP + Village* (Donor & Thematic derive automatically). While a workspace is **active**, every form you open is pre-filled with that context and those fields are hidden — you only type the record's own details.

- **＋ New workspace** — cascading pickers District → Project → Block → GP → Village/Ward, optional name (auto-named otherwise). Small **＋ New …** buttons add missing master data without losing your progress.
- **⚡ Quick start** — initialise the workspace from an HQ assignment (Project · District pre-filled).
- **📋 My assignments** — work HQ assigned to *you*; **Start ▸** turns one into a workspace in one click.
- **Workspace pills** — one pill per workspace. Click to **switch** (multiple officers / multiple projects — keep as many as you need). **✎** edit/rename · **✕** remove. **Pause** enters without any workspace.
- Workspaces are stored **on the server**: they follow you to any device, admins see them in MIS Status, and HQ can assign new ones that appear here automatically.
- **🎯 HQ Targets for this workspace** — the matching plan (blocks/villages/beneficiaries) shows on the workspace and inside forms.

**Entry cards** — + New Beneficiary · Progress of Production & Output · + New Activity · + New SHG · + New SHG Loan · Indicator Progress. Cards are dimmed until a workspace is active (you can still open them, un-prefilled). Each card shows a live record count.

### 5.3 Importing data from Excel

Two importers, both at the bottom of Field-Office Entry:

**Per-section import** (one card per section):
1. **⬇ Download Template** — headers with **red = required**, hover any header for allowed values & format tips, a grey example row, and a "How to fill" help sheet. Type *names* for Donor/Project/Thematic/SHG/Indicator — the importer links them to the right IDs by fuzzy matching. Dates accept dd/mm/yyyy, dd-mm-yyyy or yyyy-mm-dd; money accepts ₹, commas, K/L/Cr.
2. Drop your file → **Review screen** shows every row with auto-corrections (green), warnings (yellow) and errors (red) *before anything is saved*.
3. **Import N valid rows →** — imports with full validation and audit trail. Skipped rows can be downloaded as a CSV, fixed, and re-uploaded.

**🪄 Smart Multi-Sheet Import** — drop a whole workbook; every sheet is auto-routed to the right section (you can override the routing). After import, **⬇ Download failed rows (Excel)** gives every rejected row with a "⚠ Why it failed" column — fix, delete that column, re-import the same file.

---

## 6. How every form behaves (shared features)

- **IDs are automatic** — BEN-0001, PRJ-001, SHG-001… generated safely even when many people save at the same moment. You never type an ID.
- **Searchable pickers** — type to search any dropdown (project, donor, beneficiary, indicator…).
- **Auto-fill linkage** — picking a Project fills its Donor & Thematic Area; picking a Geography fills District/Block/GP/Village; picking a Beneficiary (in Production) fills their context; picking an SHG (in Loans) fills its whole context; picking an Indicator fills its Thematic/Project.
- **＋ New … buttons** under pickers — open the real master form; when you save, you return to your original form with everything you had typed restored and the new record already selected.
- **🔒 Lock these fields** — keeps context fields (incl. Country/State/District/Village) filled across many entries *on your screen only*. Workspaces replace this when active.
- **Add another with same data** — after saving a Beneficiary or Production record, one click opens a fresh form carrying over the context fields.
- **Duplicate guard** — saving a beneficiary with the same name + father/spouse + village (or a duplicate production record) warns you first.
- **Unsaved-changes guard** — leaving a form with typed data asks before discarding.
- **Double-click-proof Save** — the button disables and shows "Saving…" until the server responds; a double click can never create two records.
- **"Added by / Last updated by"** — every record remembers who created and who last changed it (shown when editing; included in exports).
- **Validation** (friendly messages, nothing silently wrong): age 0–120 · phone must be a valid 10-digit Indian mobile (+91/0 accepted and cleaned) · money/quantities can't be negative (loss/variance can) · all dates must be real dates with sane years · end dates can't precede start dates · registration/joining/loan dates can't be in the future · Reporting Year must look like 2025 or 2025-26.

---

## 7. Master Data sections

**Donor Mappings** — the table that links donors to projects (see §15). The Project picker here shows **all** projects, flagging unlinked ones with "⚠ needs donor" so they're easy to find and fix.

**Donors** — card view: logo initials, type badge, contact, approved budget, funded-projects and beneficiary counts. A donor funding nothing shows **"⚠ Not funding any project yet"** with a map-a-project link.

**Thematic Areas** — the programme areas with their objectives.

**Projects** — table with the **Donor / Funding** column (donor chip, or a loud **⚠ Unmapped** badge). A banner counts unmapped projects and links to the fix. The form's **"Funded by — Donor" is required** — saving creates/updates the mapping automatically. Status (Planned/Ongoing/Completed) derives from the start/end dates automatically.

**Geography** — Country → State → District → Block → GP → Village hierarchy. Country defaults to **India**; the form remembers your last State/District/Block for fast bulk entry. A coverage summary strip shows counts.

**Village Demographics** — per-village household/population profile with a Coverage % in exports.

---

## 8. Field Data sections

**Beneficiaries** — the master list of people. **Registration Date pre-fills with today** (editable; a blank still saves as today, so no one ever disappears from date-filtered reports). Baseline income breakdown (paddy/millet/vegetable/mushroom/goat/poultry/micro-enterprise) with a live auto-total; income-impact modal per beneficiary with a year-by-year trend.

**Production & Output Progress** — actuals against HQ output targets: any intervention output (crops, livelihoods, health…). Picking the beneficiary fills their context; the beneficiary picker can be narrowed by geography.

**Activities & Targets** — activity lines with yearly/quarterly targets. **Y1 Achievement and Cumulative are calculated automatically** from the quarter/year cells — they can never drift.

---

## 9. SHG & Finance sections

**Self-Help Groups** — SHG register (members, savings, linkage details).

**SHG Loans** — "🔎 Find SHG by Thematic Area / Project" narrows the SHG picker; choosing the SHG silently fills its Donor/Thematic/Project (those fields are not even shown). You enter only the loan's own details.

---

## 10. Monitoring & Evaluation (MNE)

**Indicators** — defined by HQ (deliberately *not* on the field-entry page). Output/Outcome/Process/Financial, any sector, with targets on the record.

**Indicator Progress** — period-wise achievement. The **Indicator picker shows only indicators linked to the chosen project**. The 🗓 frequency bar (Monthly/Quarterly) sets the **Reporting Period automatically** (read-only) from the month or quarter + year. Quarterly figures roll up on the All-Quarters view.

---

## 11. MIS Status (admin)

The live control room — **every field officer's workspaces in one place**, refreshing every 30 seconds.

- **Stat strip** — Field staff · Workspaces · **Active now** (officers mid-work) · Assigned by HQ.
- **📌 Assign a workspace** — Officer + Project + District (Village optional — the officer can pick it later; Donor/Thematic auto). It appears in that officer's Field-Office Entry automatically.
- **Workspaces by officer** — grouped list: green dot = the workspace that officer is working in *right now*; "📌 by …" chip = HQ-assigned; **✎ rename** · **🗑 remove** per row.

---

## 12. Users & Access (admin)

**Creating / editing a user** — username, full name, email, phone, designation, organisation, **Role** (Admin / Verifier / Data Entry / Viewer / Donor Viewer), access level, password (min 6 chars). **🔑 Reset Password** kills all their sessions.

**🛡 Root protection** *(visible to root admins only)* — grants power equal to yours: full access always, manages Users & Access, and the account **can never be deleted**. Only root admins grant or revoke it; the system always keeps at least one root admin.

**Permission Matrix** — every box is the admin's decision, nothing is fixed:
- **Groups:** *Pages & modules* (Dashboard, Reports, **Data Entry — Field-Office**, **Data Entry — HQ Setup & Targets**, MIS Status, Audit Trail, Users & Access) · *Master data* · *Field data* · *Monitoring & evaluation* · *Planning & control* (HQ Targets, Field Workspaces, Custom Charts).
- **View / Edit / Delete** per row. On pages, View = can open it; Edit/Delete govern the actions inside (e.g. MIS Status: Edit = assign/rename, Delete = remove).
- **Sensitive rows** (MIS Status, Audit Trail, Users & Access) are hidden from everyone until explicitly ticked.
- **Presets:** 👷 Field officer (field entry + field data only) · 🏢 HQ staff (everything except Users & Access) · 👁 Viewer (see everything non-sensitive, change nothing) · ✕ Clear all. Apply, then fine-tune.
- **Field officers truly can't see HQ:** with only "Field-Office Entry" ticked, the HQ tab does not exist for them — not in the page, not via any switch.
- Granted user-managers cannot create/edit/delete/promote **Admin** accounts, and cannot touch **root** accounts — those stay with admins/root respectively.

---

## 13. Audit Trail

Server-side log of everything: create/update/delete (with before/after), logins, logouts, lockouts, imports, permission changes, password resets — with user, role, IST timestamp, IP and device. Passwords and tokens are never logged. Filter box searches live. Admin-only unless explicitly granted.

---

## 14. Exporting to Excel — every option

Every section's **⬇ Export ▾** menu:
- **Current view (all rows)** — the table you're looking at, formatted (styled headers, banded rows, frozen header, autofilter, auto column widths, ₹ formats, totals row on numeric tables).
- **📊 Split by Thematic Area / Project / Donor** — one sheet per thematic area / project / donor.
- **HTML print / PDF** and **Plain CSV**.

Plus: the **Reports page** one-click dimension report (§4), the **Donor Report** (20+ sheets), the **Master Workbook** (full backup), the **Import templates**, and the **failed-rows fix file**. All workbooks share the same professional formatting; achievement % cells are traffic-light coloured.

---

## 15. The donor↔project linkage

**Why it matters:** every auto-fill and every donor report follows the chain *Donor → Thematic Area → Project → data*. A project without a donor mapping is a broken chain — its data can never reach a donor report.

**How the system enforces it:**
1. Creating a project **requires** "Funded by — Donor" — the mapping is created automatically.
2. Unmapped projects (old data) are **hidden from all data-entry pickers**, with a visible "N projects hidden" note.
3. The Projects page shows a banner + per-row **⚠ Unmapped** badges; donor cards flag "not funding any project yet".
4. Fix any unmapped project in **Donor Mappings** (look for "⚠ needs donor" in the project picker). The moment it's mapped, all its historical data joins the donor reports.

---

## 16. Automatic protections against bad data

Concurrency-safe record IDs · double-submit guard · duplicate guards · unsaved-changes prompt · all the field validation in §6 · registration-date defaults (today) on form, API **and** import · date-order and future-date rules · auto-computed activity totals · project→indicator filtering · required donor on projects. Plus security: bcrypt passwords, login lockout, single-session tokens, permission checks on the server, escaped output everywhere, CDN scripts integrity-pinned, no directory listings, no SQL files on the server, safe error messages (details go to the server log only).

---

## 17. Deployment & maintenance

**Files on the server** (`public_html`): `index.html`, `api/api.php`, `api/config.php` (your DB credentials — never in git), `api/index.html` (blank on purpose), `viewsindialogo.png`.

**Never upload** the `sql/` folder — those files are only for phpMyAdmin imports.

**Database upgrades** (phpMyAdmin → Import, each idempotent/safe to re-run): `upgrade6.sql` (HQ targets) → `upgrade7.sql` (assignments) → `upgrade8.sql` (created_by/updated_by) → `upgrade9.sql` (server-side workspaces).

**Updating the app** = replace `index.html` and/or `api/api.php`, then hard-refresh the browser (Ctrl/Cmd+Shift+R).

**Backups** — Reports → **Master Workbook** exports every table to Excel in one click; cPanel database backup covers the rest.

---

## 18. Troubleshooting

| Message / symptom | Meaning | Fix |
|---|---|---|
| "Session expired — please sign in again" | Idle timeout or you signed in elsewhere | Sign in again |
| "Account locked until …" | Too many wrong passwords | Wait, or admin resets the password |
| "A required table or column is missing — run the /sql upgrade files" | A DB upgrade wasn't imported | Run upgrade6→9 in phpMyAdmin |
| "Workspaces table not found" (MIS Status) | upgrade9.sql not yet run | Import `sql/upgrade9.sql` once |
| "Duplicate value — a record with this key already exists" | The database blocked a duplicate | Check the existing record first |
| "N projects hidden — no donor linked yet" | Unmapped projects (see §15) | Map them in Donor Mappings |
| "⚠ Select the Donor funding this project" | Projects require a funder | Pick the donor (or ＋ New donor) |
| "End Date cannot be before Start Date" / "cannot be in the future" / "the year looks wrong" | Date validation (§16) | Correct the date |
| Excel button says "Excel engine still loading…" | The library is still downloading | Wait 2–3 seconds, click again |
| Old screen after an update | Browser cache | Hard-refresh: Ctrl/Cmd+Shift+R |
| Changes I made aren't visible to a colleague | They haven't refreshed / different filters | Refresh; check the filter chips |

---

*VIEWS India MIS — handover edition. Keep this file with the project; it describes the system exactly as deployed.*
