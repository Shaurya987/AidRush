# VIEWS India MIS — The Complete User Guide

*Your organisation's Management Information System for livelihoods programmes — this guide explains every screen, every button, and every connection, in plain language.*

---

## Table of contents

1. [What this system is, and the one idea that connects everything](#1-what-this-system-is)
2. [Signing in](#2-signing-in)
3. [Finding your way around the screen](#3-finding-your-way-around)
4. [The Dashboard — your programme at a glance](#4-the-dashboard)
5. [Reports & Analytics — answers on demand](#5-reports--analytics)
6. [Data Entry — how information enters the system](#6-data-entry)
   - 6.1 [The two-tier idea: HQ plans, the field reports](#61-the-two-tier-idea)
   - 6.2 [HQ Setup & Targets — planning the work](#62-hq-setup--targets)
   - 6.3 [Workspaces — the field officer's best friend](#63-workspaces)
   - 6.4 [My Assignments — work that comes to you](#64-my-assignments)
   - 6.5 [Entering each kind of record](#65-entering-each-kind-of-record)
   - 6.6 [Bringing data in from Excel](#66-bringing-data-in-from-excel)
7. [The building blocks (Master Data) — and why order matters](#7-the-building-blocks)
8. [Working with beneficiary and field data](#8-working-with-field-data)
9. [Self-Help Groups and Loans](#9-self-help-groups-and-loans)
10. [Indicators and Indicator Progress](#10-indicators-and-indicator-progress)
11. [MIS Status — the supervisor's control room](#11-mis-status)
12. [Users & Access — deciding who sees and does what](#12-users--access)
13. [The Audit Trail — the system's memory](#13-the-audit-trail)
14. [Getting data out — every export explained](#14-getting-data-out)
15. [Why the system sometimes stops you (and why that's good)](#15-why-the-system-sometimes-stops-you)
16. [Everyday questions](#16-everyday-questions)

---

## 1. What this system is

VIEWS India MIS is the single place where your organisation's programme reality lives: who funds what, where the work happens, who benefits, what was achieved against what was planned — and it turns all of that into donor-ready reports in one click.

### The one idea that connects everything: the chain

Everything in this system hangs on one chain:

> **Donor → Thematic Area → Project → the actual work (beneficiaries, outputs, activities, SHGs, loans, indicator progress)**

- A **Donor** gives money.
- That money funds work under a **Thematic Area** (livelihoods, health, education…).
- The work is organised into **Projects**.
- Every record you enter — a beneficiary, a harvest, a training, a loan — belongs to a project, and *through the project*, to a thematic area and a donor.

**Why this matters to you every single day:** when a donor asks *"show me what my money achieved"*, the system walks this chain backwards and gathers everything automatically. If a record is entered without its project connection, it becomes an orphan — real work that no donor report can ever find. That is why the system is strict about the chain (you will see this strictness in action in §15), and why the very first thing done for any new project is naming its funding donor.

### The second idea: plan first, then report progress

HQ sets **targets** (how many beneficiaries, villages, blocks; what outputs). Field teams then enter **actuals**. Every progress screen and report compares the two — that comparison (achievement %) is the heartbeat of the whole system, and it is colour-coded everywhere the same way:

- 🟢 **Green — 80% or more achieved** (on track)
- 🟡 **Amber — 40–79%** (needs attention)
- 🔴 **Red — below 40%** (intervene)

Keep these two ideas in mind and every screen in this guide will feel obvious.

---

## 2. Signing in

1. Open the MIS in your browser.
2. Enter your **username (or email)** and **password**, press **Sign in**.

**Things the system does for your safety — so they don't surprise you:**

- **Wrong password several times in a row?** The account locks itself for a few minutes. This is deliberate — it stops someone from guessing passwords. Wait a little and try again, or ask your administrator to reset your password.
- **One person, one session.** If you sign in on a second computer or tab, the first one is signed out. This prevents shared logins, which would make the record of "who entered what" meaningless.
- **Walked away for a long time?** The system signs you out automatically. Your unsaved typing is not kept — save before long breaks.
- **Every sign-in and sign-out is remembered** in the Audit Trail (§13), with time and device. This protects *you*: if something was changed under your name, the trail shows exactly when and from where.

---

## 3. Finding your way around

**The left sidebar** is the map of the whole system. You only see what your access allows (§12) — so two colleagues may see different menus, and that is intentional.

- **Overview** — Dashboard · Reports & Analytics · Data Entry (where you will live day-to-day)
- **Master Data** — Donor Mappings · Donors · Thematic Areas · Projects · Geography · Village Demographics (the building blocks — set up once, touched rarely)
- **Field Data** — Beneficiaries · Production & Output Progress · Activities & Targets
- **SHG & Finance** — Self-Help Groups · SHG Loans
- **Monitoring and Evaluation (MNE)** — Indicators · Indicator Progress
- **Admin** — MIS Status · Users & Access · Audit Trail (management and administrators)

**On almost every page, top-right, you will find:**

| Element | What it is for |
|---|---|
| **● N records** pill | Live count of records in this section — a quick health check |
| **⬇ Export ▾** | Every way to take this section's data out (see §14) |
| **+ New** | Add a record (only appears if you have permission to add) |

**A quiet convenience:** the Dashboard refreshes itself every half-minute. Numbers and charts update in place — you never need to reload the page during a review meeting.

---

## 4. The Dashboard

The Dashboard answers one question: *"How is the programme doing right now?"* Every element on it obeys the filter bar, so it can answer that question for the whole organisation, one thematic area, or one project.

### The filter bar

- **Thematic Area** — narrow everything to one theme. The **Project** list then shows only that theme's projects (the chain at work).
- **Project** — narrow to a single project.
- Your active choices appear as small **chips** you can remove one by one; **✕ Clear** resets everything.

*Why filters matter:* the same Dashboard is your all-organisation review AND your single-project review. Before reading any number, glance at the chips to know what you are looking at.

### The number cards (KPIs) — what each one really means

| Card | What it counts | Why you care |
|---|---|---|
| **Thematic Areas** | Your programme themes | The breadth of your mission |
| **Active Donors** | Donors currently funding at least one project | "Active" is the key word — a donor in the address book who funds nothing is not counted here |
| **Projects** | Implementation projects | Your delivery units |
| **Districts / Blocks / Villages** | Places where you actually have records | Your true geographic footprint — grows automatically as data is entered |
| **Beneficiaries** | People registered | The heart number |
| **SHGs** | Self-help groups formed/supported | Community institutions built |
| **Activities** | Activity lines being tracked | How much delivery is being measured |
| **Avg. Income Change** | Average household income now vs at registration | The single strongest impact number you have — it exists because you record baseline income when registering a beneficiary (§8) |

### The charts — and the story each one tells

- **Beneficiaries by District & Block** — where your people are, in one picture. Use it to spot places that are under-served.
- **Social Order / Gender / Religion** — inclusion at a glance. Donors ask for exactly these breakdowns; here they are, live.
- **Household Income — Before vs After** — the impact chart. It compares income at registration (baseline) with current income. The **Bar / Line** toggle button switches views: bars for comparing groups, line for seeing the trend over years. This chart is only as good as the baseline data entered on the beneficiary form — one more reason that form matters (§8).
- **Top Activity Performance** — each major activity's target vs cumulative achievement with a precise % — your green/amber/red early-warning list.
- **Data Completion Status** — how completely each section's important fields are filled. A low bar here means reports from that section will have gaps. Use it to steer data-cleaning drives.
- **Objectives panels** — thematic-area and project objectives with their status, so strategy and data sit on the same page.

**⬇ Export ▾** on the Dashboard downloads all of this as a formatted Excel; **Print/PDF** makes a meeting-ready page.

---

## 5. Reports & Analytics

If the Dashboard is "how are we doing", Reports is **"give me the report by ___"** — by donor, by district, by project, by SHG… assembled live from the data, always current.

### The report bar — four choices, one perfect report

1. **Report:** the *dimension* — Donor-wise · Thematic Area-wise · Project-wise · District-wise · Block-wise · Village/Ward-wise · SHG-wise. This decides the lens.
2. **The second dropdown** — the specific donor / district / project… or **All**.
3. **⏱ Duration** — *All time*, *Monthly* (current month), *Quarterly* (current quarter), *Annually* (current Indian financial year, April–March), or **Custom range…** which opens **From / To** calendars for any period a donor asks about. The duration filters beneficiaries by their registration date — which is why the system never allows a blank registration date (§15).
4. **⬇ Download this report (Excel)** — the one-click deliverable. It builds a complete, formatted workbook for exactly your three choices above: a summary page, activity performance by quarter, indicator progress, demographic and geographic breakdowns, production outputs, SHG summary, loans by financial year, and donor budget — with all achievement percentages traffic-light coloured. **This is the button to press when someone says "send me the report".**

Below the bar, a **stat strip** (Districts · Blocks · Villages · Beneficiaries · SHGs for your current filter) and the **on-screen version of the report** update live as you change choices — so you can check before downloading.

### The One-Click Donor Report

Further down the page is a special builder for the most demanding audience: a single donor. Pick the donor (and a period if you wish) and generate — you receive a **20+ sheet, submission-ready workbook**: executive summary, donor profile, every funded project, beneficiary analysis with inclusion breakdowns, income impact, activities, indicators, SHGs, loans, budget vs utilisation, and even a glossary so the donor's own staff can read it without calling you.

### The Master Workbook

One click exports **every section of the entire database** into one formatted Excel file. Two uses: a complete offline copy for anyone who asks, and your **do-it-yourself full backup** — a good habit before any big data drive.

---

## 6. Data Entry

### 6.1 The two-tier idea

Open Data Entry and you see two tabs:

- **🏢 HQ Setup & Targets** — where the plan is made. *Who can see this tab is controlled per user* — field staff typically cannot.
- **📍 Field-Office Entry** — where the actual data is entered against the plan.

This separation exists for a simple reason: **targets are commitments**. If everyone could edit targets, achievement percentages would mean nothing. So planning is one permission, entering is another (§12), and never the twain shall meet unless the administrator says so.

### 6.2 HQ Setup & Targets

This is where a manager translates a funded project into concrete, measurable expectations.

**Step by step:**

1. **Project** — pick the project. **Donor and Thematic Area appear by themselves** — you never type them, because the chain (§1) already knows them. *If a project you expect is missing from this list, it has not been linked to a donor yet — see §15.*
2. **District** — pick the district this plan covers.
3. **Targets** — type the planned numbers: **Total Blocks**, **Total Villages**, **Total Beneficiaries**. These become the yardsticks every field entry is measured against.
4. **Assigned to (officer)** — choose the field officer responsible. This single dropdown is what makes the work *appear automatically on that officer's screen* (§6.4). Assigning work is literally one click here.
5. **Activities · Indicators · Output** — deeper targets are not typed as bare numbers; the **＋ Add activity & target / ＋ Add indicator & target / ＋ Add output** buttons open the real forms, where each target lives on its own record with its own details. This keeps targets rich (units, years, quarters) instead of a single number.
6. **💾 Save HQ Targets** — saves this district's plan. The form clears, ready for the next district — because one donor often funds many districts, you repeat from step 2 as needed.
7. **Existing HQ Targets** — the table below lists every plan row (Donor · Thematic · Project · District · targets · Assigned to), each with a delete button if a plan changes.

The small **＋ New project / donor / thematic area / district** buttons exist for the moment you discover mid-planning that something is missing from a list — they open the proper form and **bring you back with everything you had filled still intact**.

### 6.3 Workspaces

*The single biggest time-saver in the system.*

A field officer's day involves entering many records that share the same context: same project, same district, same village. Typing that context again and again is wasted time and invites mistakes. A **workspace** is that context, saved once:

> **Project + District + Block + Gram Panchayat + Village** (Donor and Thematic Area come along automatically — the chain again).

While a workspace is **active**, every form you open is already filled with this context — those fields do not even appear; a small banner shows the context instead. You type only what is new: the person's name, the harvest quantity, the loan amount.

**Everything you can do with workspaces:**

- **＋ New workspace** — choose District → Project → Block → GP → Village step-by-step (each list narrows the next). Name it what you like — "Ganjam · Millet · Badagada" — or leave the name blank and the system names it sensibly.
- **⚡ Quick start** — instead of picking piece by piece, start from an HQ assignment: project and district pre-filled, you add the village.
- **Switch with one click** — every workspace is a pill at the top. Working two projects in one day? Two pills; click to switch. There is no limit — keep one per village if you like.
- **✎ Rename / edit** and **✕ Remove** on each pill. Removing a workspace **never touches the records you entered with it** — it only removes the shortcut.
- **Pause** — enter something outside any context (forms open blank).
- **Your workspaces follow you.** They are saved centrally, so they appear on whatever computer you sign in from — and your supervisor can see who is working where (§11), which is a feature, not surveillance: it is how help gets sent where it is needed.
- **🎯 HQ Targets for this workspace** — the matching plan numbers (blocks, villages, beneficiaries) are shown on the workspace and inside the forms — so you always know what you are working toward.

**The entry cards** (New Beneficiary, Progress of Production & Output, New Activity, New SHG, New SHG Loan, Indicator Progress) sit below. They look dimmed until a workspace is active — a gentle reminder to set your context first (you *can* still open them without one; they simply will not be pre-filled). Each card shows a live count of records already in that section.

### 6.4 My Assignments

If HQ has assigned work to you (§6.2 step 4), a **📋 My assignments** panel appears with each work unit — project, district, and the beneficiary target. Press **Start ▸** and it becomes a ready workspace instantly. This is the complete loop: *HQ plans → assigns → you press one button → you are entering data in the right context.*

### 6.5 Entering each kind of record

Every form shares the same conveniences — learn them once, use them everywhere:

- **The ID makes itself.** BEN-0001, SHG-014… appear on save. You never invent numbers, and two colleagues saving at the same instant can never collide.
- **Every dropdown is searchable.** Click and type a few letters — no scrolling through hundreds of names.
- **Choices fill other fields.** Pick a Project → its Donor and Thematic Area fill in. Pick a Geography → District/Block/GP/Village fill in. Pick a Beneficiary (in Production) → their whole context arrives. This is the chain saving you typing at every step.
- **＋ New under a dropdown** — the thing you need isn't in the list? Add it *right there*: the real form opens, you save, and you are returned to your half-finished entry with the new item already selected. Nothing you typed is lost.
- **🔒 Lock these fields** — entering twenty records with the same context but no workspace? Lock keeps the context fields filled between saves, on your screen only.
- **"Add another with same data"** — after saving a Beneficiary or Production record, one click starts the next one with the shared context carried over.
- **Tab** moves between fields; **Enter** on the last field saves. The **Save** button shows "Saving…" and will not fire twice, no matter how enthusiastically it is clicked.
- **Trying to leave with unsaved typing?** The system asks first. Nothing is ever silently thrown away.
- **Duplicate warnings** — registering a beneficiary whose name + father/spouse + village already exist (or an identical production record) triggers a warning *before* saving, so double-entries get caught at the door.
- **"Added by / Last updated by"** — when you edit any record, a line shows who created it and who last changed it. Accountability, built in.

*(Field-by-field guidance for each record type is in §§7–10.)*

### 6.6 Bringing data in from Excel

You have data in spreadsheets — old registers, survey exports. The system takes them in two ways, both at the bottom of Field-Office Entry.

**A. Per-section import** (one card per section):

1. **⬇ Download Template.** This is not a bare header row — it is a guided form in Excel:
   - **Red headers = required** columns; dark headers are optional.
   - **Hover any header** for a note listing allowed values and formats.
   - A grey *example row* shows exactly how a filled row looks (replace it with your data).
   - A **"How to fill"** sheet answers common questions.
   - For Donor / Project / Thematic / SHG / Indicator columns you type the **name**, not any code — the importer recognises names even with small spelling differences and links them correctly.
   - Dates in dd/mm/yyyy, dd-mm-yyyy or yyyy-mm-dd all work. Money accepts ₹, commas, and Indian shorthand (5K, 1.5L, 2Cr).
2. **Drop your file.** Before anything is saved you see the **review screen**: every row, with green highlights where the importer auto-corrected something, yellow where it wants your attention, red where a row cannot be accepted (with the reason).
3. **Import N valid rows →** does exactly what it says. Skipped rows can be downloaded, corrected and re-uploaded — no row is ever lost silently.

**B. 🪄 Smart Multi-Sheet Import** — for a whole workbook at once. Every sheet is recognised and routed to the right section automatically (you can override any routing before importing). Afterwards, if anything was rejected, **⬇ Download failed rows (Excel)** gives you every failed row **with a "⚠ Why it failed" column in plain words**. Fix the cells, delete that column, re-import the same file. Repeat until zero failures — a clean, complete migration with no data left behind.

---

## 7. The building blocks

Master Data is set up early and touched rarely — but understanding it explains why the rest of the system behaves as it does. **Set-up order matters** because of the chain: *Thematic Areas → Donors → Projects (naming their donor) → Geography → then daily data.*

**Thematic Areas** — your programme themes (livelihoods, health…). Each carries its objectives with status, which feed the Dashboard's objectives panel. Everything else files itself under a thematic area through the project.

**Donors** — every funder, shown as cards: type badge (CSR / Foundation / Government…), contact person, email/phone, **approved budget**, and live counts of funded projects and reached beneficiaries. A card showing **"⚠ Not funding any project yet"** means this donor exists in the address book but the chain hasn't been connected — until it is, nothing can appear in that donor's reports. The card links you straight to the fix.

**Projects** — your delivery units. Key points:
- **"Funded by — Donor" is required.** You cannot create a project without naming who pays for it — this is the moment the chain is forged, and it is why every later record automatically knows its donor.
- **Status is automatic** — Planned / Ongoing / Completed derives from the start and end dates you enter. No one "forgets" to update a status.
- The table's **Donor / Funding column** shows each project's funder as a chip — or a loud **⚠ Unmapped** badge for older projects created before this rule. A banner at the top counts them and links to the repair (§15).

**Donor Mappings** — the ledger of who-funds-what, with the funded component, budget head, and approved amount. Mostly it maintains itself (created automatically when projects are made); you come here to fix ⚠-flagged projects or to record funding details. In its project picker, unlinked projects are marked **"⚠ needs donor"** so they leap out.

**Geography** — your places: Country → State → District → Block → Gram Panchayat → Village/Ward. Country pre-fills as *India*; the form remembers the last State/District/Block you typed, so adding all villages of one block is quick. Every beneficiary, SHG and record points into this tree — which is what makes district-wise and village-wise reports possible.

**Village Demographics** — the profile of each village: households, population, categories, the contact person. Recording total households is what lets the system compute **Coverage %** — how much of a village your project actually reaches — a number donors love.

---

## 8. Working with field data

**Beneficiaries** — the most important form in the system. What its parts mean:

- **Registration Date** — arrives pre-filled with **today** (change it when registering backlog). It can never be left empty and can never be in the future, because this date is how beneficiaries are counted in "From–To" reports. A person with no date would silently vanish from every period report — the system simply does not allow that to happen.
- **Identity** — name, father/spouse, gender, age (0–120), social order (SC/ST/OBC/General), religion — these power the inclusion charts and breakdowns donors require.
- **Contact number** — must be a real 10-digit Indian mobile; +91 or a leading 0 are accepted and cleaned automatically.
- **Geography** — pick the village; district/block/GP fill themselves.
- **Land & livelihood** — landholding and income source classifications.
- **Baseline income breakdown** — income at the time of registration, split by source (paddy, millet, vegetables, mushroom, goat, poultry, micro-enterprise) with a total that adds itself up. **Take two extra minutes here** — this baseline is the "before" in every Before-vs-After income chart and in the Avg. Income Change headline. Without it, impact cannot be shown for this person.
- Each beneficiary row later offers an **income view** — their baseline vs current income and a year-by-year trend.

**Production & Output Progress** — the actuals against HQ's output targets: any intervention output (a harvest, a livelihoods batch, a health camp result…). Pick the beneficiary (the picker can be narrowed by village) and their context fills in; then record the intervention, season/year, quantity, income and net profit (a loss — negative profit — is allowed, because a loss is real data). These records feed the income trend and the outputs sheets of every report.

**Activities & Targets** — delivery tracked quarter by quarter. Enter the total target and the quarterly achievements — **the yearly total and cumulative figures calculate themselves** and cannot be typed, so the achievement % you see is always arithmetic, never opinion.

---

## 9. Self-Help Groups and Loans

**Self-Help Groups** — each SHG with its village, project (and through it, donor and thematic area), members, roles and savings. The membership and savings figures roll up into the Dashboard and reports.

**SHG Loans** — recording a loan takes seconds *because of the chain*:
1. Optionally narrow with **🔎 Find SHG by Thematic Area / Project**.
2. **Pick the SHG.** That's it — the loan inherits the SHG's donor, thematic area and project silently (you won't even see those fields; there is nothing to get wrong).
3. Enter the loan's own facts: financial year, amount, source (bank linkage…), purpose, date received (never in the future), repayment status.

Repayment status appears as a colour badge in the table, and loans-by-financial-year is a standard sheet in the reports.

---

## 10. Indicators and Indicator Progress

**Indicators** — the measurable promises of your programme (trainings held, income increased…), each defined under a thematic area and project with its target. Indicators are **defined by HQ/M&E** — deliberately not on the field-entry page — so the yardsticks stay stable.

**Indicator Progress** — the field's periodic reporting against those yardsticks:

- **The indicator list shows only the chosen project's indicators.** You cannot accidentally report against another project's indicator — the chain protecting data quality again.
- **🗓 Data entry frequency** — choose Monthly or Quarterly. Pick the month (or quarter + year) and the **Reporting Period writes itself** — everyone's periods are worded identically, so grouped reports group correctly.
- **Reporting Year** accepts `2025` or the Indian financial year form `2025-26` — nothing else, so "25" vs "2025" chaos cannot enter the data.
- Enter the achievement for the period and the cumulative figure; variance may be negative (under-achievement is information, not an error).

---

## 11. MIS Status

*For supervisors and management. If you cannot see it, it has not been enabled for you (§12).*

MIS Status is a live control room showing **every field officer's workspaces** — refreshed automatically every half-minute:

- **Four headline cards** — Field staff · Workspaces · **Active now** (how many officers are inside a workspace at this moment) · Assigned by HQ.
- **📌 Assign a workspace** — pick Officer + Project + District (Village is optional — the officer can choose it when they start; donor and thematic fill themselves). The workspace appears on that officer's screen automatically. This and §6.2's "Assigned to" are the two ways of sending work to people.
- **Workspaces by officer** — every officer with their workspaces beneath: a **green dot** marks the one they are working in right now; a **📌 by …** chip marks HQ-assigned ones; each row shows project, place, creation date, with **✎ rename** and **🗑 remove** (removing a workspace never touches any data the officer entered).
- Officers with no workspace yet show *"No workspace yet — assign one above"* — your to-do list for onboarding.

---

## 12. Users & Access

*For administrators. This is where you decide, person by person, exactly what the system shows and allows.*

### Creating a user

**+ New** in Users & Access: username, full name, email, phone, designation, organisation, **Role**, access level, and a starting password (min 6 characters — encourage longer). **🔑 Reset Password** on an existing user sets a new password *and signs them out everywhere at once* — the button to use the moment a phone is lost.

**Roles** are broad types: **Admin** (everything, including this section) · **Verifier** · **Data Entry** · **Viewer** · **Donor Viewer**. The Permission Matrix below then fine-tunes any non-admin to exactly what you want.

### The Permission Matrix — every box is yours to decide

A table of everything in the system, grouped for sanity, with **View / Edit / Delete** boxes on every row:

- **Pages & modules** — can they *open* Dashboard? Reports? **Data Entry — Field-Office Entry**? **Data Entry — HQ Setup & Targets**? MIS Status? Audit Trail? Users & Access? Note that Data Entry is **two separate boxes** — this is how a field officer sees only their tab while a manager sees both. On pages, Edit/Delete control the actions inside (e.g. on MIS Status, Edit = assign & rename workspaces, Delete = remove them).
- **Master data / Field data / M&E / Planning & control** — per record type: View (see it), Edit (add & change), Delete (remove). Example: field officers usually get Edit on Beneficiaries but no Delete — mistakes are edited, not erased.
- **Sensitive rows** — MIS Status, Audit Trail and Users & Access are hidden from everyone until you explicitly tick them. Handing someone Users & Access is delegation of trust: they can manage users, but they can never create or promote an Admin, and can never touch a root-protected account.

**Preset buttons** give you a sound starting point in one click — then adjust any box:
- **👷 Field officer** — Field-Office Entry + the field data they enter. No HQ tab, no admin pages, no delete.
- **🏢 HQ staff** — everything except Users & Access.
- **👁 Viewer** — sees everything non-sensitive, changes nothing.
- **✕ Clear all** — start from zero.

### 🛡 Root protection

Visible only to root administrators, on any user's form: a switch that grants **power equal to your own** — full access to everything always, Users & Access management, and an account that **can never be deleted**. Grant it only to a person you trust as you trust yourself (a co-director, a successor). Two guarantees are built in: only a root admin can grant or remove root, and the system will never let the *last* root admin be removed — the organisation can never lock itself out.

### Recipes

| You want… | Do this |
|---|---|
| A field officer who enters data for their villages only | Create user → preset **👷 Field officer** → save. Then assign their work (§6.2/§11) |
| A programme manager | Preset **🏢 HQ staff**; tick MIS Status if they supervise field teams |
| A donor who may look but not touch | Role **Donor Viewer** → preset **👁 Viewer** |
| An M&E person who defines indicators | Viewer preset + Edit on Indicators & Indicator Progress |
| A second administrator who can never be locked out | Role **Admin** + tick **🛡 Root protection** |

---

## 13. The Audit Trail

The system's memory. Every meaningful event writes a line: record created / changed / deleted (with what it looked like before), sign-ins and sign-outs, failed login lockouts, imports, permission changes, password resets — each with **who, when (IST), from which address and device**.

**How to read a line:** the coloured badge is the action (green create, amber update, red delete…), then the plain-language message, then the person, role, time and device. The filter box narrows thousands of lines to what you need ("delete", a username, a record ID).

**What you will never find here:** passwords or security tokens — they are scrubbed before logging, always.

*Why it exists:* accountability without arguments. "Who changed this target?" takes ten seconds to answer, with evidence.

---

## 14. Getting data out

Everything the system knows can leave it, formatted and presentable. Choosing the right export:

| You need… | Use |
|---|---|
| This section's table, nicely formatted | **Export ▾ → Current view (all rows)** — styled headers, banded rows, frozen header row, filters on, ₹ formats, totals row |
| The same, one sheet per thematic area / project / donor | **Export ▾ → Split by Thematic Area / Project / Donor** |
| Something to print or PDF | **Export ▾ → HTML print / PDF** |
| Raw data for another software | **Export ▾ → Plain CSV** |
| "Send me the district report for April–June" | Reports page → dimension + duration → **⬇ Download this report** (§5) |
| A donor's full submission pack | Reports page → **One-Click Donor Report** (§5) |
| Everything, as a backup or archive | Reports page → **Master Workbook** |
| A form to collect data in Excel | Any import card → **Download Template** (§6.6) |

Every workbook arrives styled the same professional way, with achievement percentages traffic-light coloured — ready to attach to an email as-is.

---

## 15. Why the system sometimes stops you

Every "no" from this system protects a report somewhere downstream. The rules, and their reasons:

| The system insists… | Because otherwise… |
|---|---|
| A project must name its funding **Donor** | Its data could never appear in any donor report — work would become invisible to the people who paid for it |
| **Unmapped projects don't appear** in entry forms (with a note saying how many are hidden) | Entering data against a broken chain creates orphan records; the note + Donor Mappings' "⚠ needs donor" flags make the repair a two-minute job — and the moment a project is mapped, *all* its past data joins the donor's reports |
| **Registration Date fills itself with today** and can't be blank or in the future | A blank date makes a person invisible to every From–To report; a future date mis-files them |
| Dates must be real, with sensible years; **End can't be before Start** | One mistyped year (a "0202") silently breaks every date-filtered report that touches it |
| Phone numbers must be valid 10-digit mobiles; age 0–120; quantities can't be negative (losses can) | Garbage in, garbage in every report out |
| **Duplicate warning** on familiar-looking beneficiaries | Double-counted people inflate every number donors check |
| **"You have unsaved changes"** when leaving a form | Half an hour of typing should never vanish from a stray click |
| The Save button works **once** per save | An impatient double-click must not create two records |
| Yearly/cumulative activity totals **calculate themselves** | Typed totals drift from their parts; calculated ones cannot |
| Only the chosen project's **indicators** appear | Progress reported against the wrong project's yardstick corrupts both projects' reports |
| Some colleagues **can't see** some pages | That is your administrator's Permission Matrix working exactly as configured (§12) |

When you hit one of these, the message tells you exactly what to adjust. None of them ever discards your other typing.

---

## 16. Everyday questions

**"A project is missing from my dropdown."**
It has no donor linked yet. Anyone with master-data access: Donor Mappings → find it flagged "⚠ needs donor" → link the donor. It appears everywhere immediately.

**"My report shows fewer beneficiaries than I expected."**
Check two things: the **filter chips** (are you looking at one project only?) and the **⏱ Duration** (a custom range counts people by registration date — those registered outside the range are correctly excluded).

**"My colleague can't see the HQ tab / MIS Status / a section."**
By design — their permissions (§12). An administrator can change it in two clicks.

**"I need to correct a record."**
Open its section, use the ✎ edit button on the row, correct, Save. The Audit Trail keeps the before-and-after, and the record will show you as "Last updated by".

**"I deleted a workspace — did I lose my entries?"**
No. Workspaces are shortcuts, not containers. Every record you entered is safe in its section.

**"Someone forgot their password."**
Users & Access → their row → ✎ → **🔑 Reset Password**. They sign in with the new one; all their old sessions are closed automatically.

**"Two of us entered data at the same time — is that OK?"**
Completely. The system is built for many people working at once — IDs never collide, and everyone's work is stamped with their own name.

**"Where do I see who changed something?"**
Edit the record ("Added by / Last updated by" line) or search the Audit Trail for the full history.

**"The Excel button said the engine is still loading."**
The Excel component loads a moment after the page. Wait two seconds, click again.

**"I imported a file and some rows failed."**
Download the failed-rows file the import screen offers — every row carries a plain-words reason. Fix, delete the reason column, re-import the same file. Nothing was lost.

---

*VIEWS India MIS — built from the ground up for your programme. Keep this guide beside the system; it describes exactly what every screen does and why.*
