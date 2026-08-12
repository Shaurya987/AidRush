# VIEWS MIS — The Complete User Guide

*Your organisation's Management Information System for livelihoods programmes — this guide explains every screen, every button, and every connection, in plain language.*

> **This guide is also built into the application.** Open **Admin, then User Manual** to read the same explanations on screen, section by section, with a search box. That copy explains every card, every chart and every button in plain language.

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
10. [Logged Indicators — the 11 computed M&E indicators](#10-logged-indicators-me)
11. [MIS Status — the supervisor's control room](#11-mis-status)
12. [Users & Access — deciding who sees and does what](#12-users--access)
13. [The Audit Trail — the system's memory](#13-the-audit-trail)
14. [Getting data out — every export explained](#14-getting-data-out)
15. [Why the system sometimes stops you (and why that's good)](#15-why-the-system-sometimes-stops-you)
16. [Everyday questions](#16-everyday-questions)
17. [Messages and errors you may see](#17-messages-and-errors-you-may-see)

---

## 1. What this system is

VIEWS MIS is the single place where your organisation's programme reality lives: who funds what, where the work happens, who benefits, what was achieved against what was planned — and it turns all of that into donor-ready reports in one click.

### The one idea that connects everything: the chain

Everything in this system hangs on one chain:

> **Donor → Project → Geography → the actual work (beneficiaries, outputs, activities, SHGs, loans, governance records)**

- A **Donor** gives money.
- That money funds **Projects** (the organisation runs one thematic area — Livelihood — so it is implicit, never asked for).
- Each project is assigned the **places** it works in.
- Every record you enter — a beneficiary, a harvest, a training, a loan, a Gram Sabha meeting — belongs to a project and a place, and *through the project*, to a donor.

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
- **Master Data** — Donor Mappings · Donors · Projects · Geography · Village Demographics (the building blocks — set up once, touched rarely)
- **Field Data** — Beneficiaries · Production & Output Progress · Activity Progress
- **SHG & Finance** — Self-Help Groups · SHG Loans
- **Local Governance** — one section with two tabs: Gram Sabha / VDC and Convergence — Govt Schemes
- **Monitoring and Evaluation (MNE)** — Logged Indicators (the 11 computed indicators)
- **Admin** — MIS Status · Users & Access · **User Manual** (this guide, built into the app) · Audit Trail (management and administrators)

The interface carries the organisation's two colours — a deep navy for identity and actions, and a warm gold for highlights. Status colours are deliberately left alone: a warning is always amber, an error always red, success always green.

**On almost every page, top-right, you will find:**

| Element | What it is for |
|---|---|
| **● N records** pill | Live count of records in this section — a quick health check |
| **⬇ Export ▾** | Every way to take this section's data out (see §14) |
| **+ New** | Add a record (only appears if you have permission to add) |

**A quiet convenience:** the Dashboard refreshes itself every half-minute. Numbers and charts update in place — you never need to reload the page during a review meeting.

---

## 4. The Dashboard

The Dashboard answers one question: *"How is the programme doing right now?"* Every element on it obeys the filter bar, so it can answer that question for the whole organisation, one donor, or one project.

### The filter bar

- **Project** — narrow everything to a single project.
- **Donor** — narrow to one funder; the Project list then shows only what that donor funds (the chain at work).
- Your active choices appear as small **chips** you can remove one by one; **✕ Clear** resets everything.

*Why filters matter:* the same Dashboard is your all-organisation review AND your single-project review. Before reading any number, glance at the chips to know what you are looking at.

### The number cards (KPIs) — what each one really means

| Card | What it counts | Why you care |
|---|---|---|
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

1. **Report:** the *dimension* — Donor-wise · Project-wise · District-wise · Block-wise · Village/Ward-wise · SHG-wise. This decides the lens.
2. **The second dropdown** — the specific donor / district / project… or **All**.
3. **⏱ Duration** — *All time*, *Monthly* (current month), *Quarterly* (current quarter), *Annually* (current Indian financial year, April–March), or **Custom range…** which opens **From / To** calendars for any period a donor asks about. The duration filters beneficiaries by their registration date — which is why the system never allows a blank registration date (§15).
4. **⬇ Download this report (Excel)** — the one-click deliverable. It builds a complete, formatted workbook for exactly your three choices above: a summary page, activity performance by quarter, indicator progress, demographic and geographic breakdowns, production outputs, SHG summary, loans by financial year, and donor budget — with all achievement percentages traffic-light coloured. **This is the button to press when someone says "send me the report".**

Below the bar, a **stat strip** (Districts · Blocks · Villages · Beneficiaries · SHGs for your current filter) and the **on-screen version of the report** update live as you change choices — so you can check before downloading.

### The One-Click Donor Report

Further down the page is a special builder for the most demanding audience: a single donor. Pick the donor (and a period if you wish) and generate — you receive a **20+ sheet, submission-ready workbook**: executive summary, donor profile, every funded project, beneficiary analysis with inclusion breakdowns, income impact, activities, indicators, SHGs, loans, budget vs utilisation, and even a glossary so the donor's own staff can read it without calling you.

Three sheets deserve a word of their own:

- **Other & Direct Income** — the four non-farm livelihoods (Goat Rearing, Backyard Poultry, Micro Enterprise, Other Income) and any record whose income was typed directly, listed on their own with the household, the year, the income, the expenditure and the net income. Keeping them apart means nobody compares a tailoring shop against a paddy yield by accident. **Every rupee still counts** in the totals, the charts and the indicators.
- **Income by Year & Activity** — the same table the screen shows when a household's income is opened, for every household at once: one row per household and activity, the baseline in its own column, then each project year with a matching *vs baseline* column. Years are never added together.
- **Outputs Detail** now carries the **standardised Livelihood / Crop** beside the words that were actually recorded (*As Recorded*), plus an **Income Basis** column stating whether the figure came from output × rate or was typed directly. A reader can see both what the software counted and what the field officer wrote.

### The Master Workbook

One click exports **every section of the entire database** into one formatted Excel file. Two uses: a complete offline copy for anyone who asks, and your **do-it-yourself full backup** — a good habit before any big data drive.

### One source of truth: reports are never out of date

**Nothing in this system stores a total.** Every figure on every screen and in every Excel file is calculated from the database at the moment you look at it. That is a deliberate design decision, and it is what makes the following true:

- Delete a production record and the income, net profit, output totals, growth figures, dashboard cards, charts and the eleven indicators all change **in the same instant** — nobody has to refresh anything.
- Delete a beneficiary and their production records go with them, so an orphaned figure can never inflate a total.
- Edit an income and every comparison that used it moves with it.
- There is no situation in which the database holds one value and a report shows another, because there is only ever one value.

Two further safeguards make certain of it: every read is sent with browser caching switched off, and a counter is raised on every save and delete which travels with every later read, so no cache anywhere can answer with the older figures.

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

1. **Project** — pick the project. **The Donor appears by itself** — you never type it, because the chain (§1) already knows it. *If a project you expect is missing from this list, it has not been linked to a donor yet — see §15.*
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

> **Project + District** (Block, Gram Panchayat and Village optional — the Donor comes along automatically, the chain again).

While a workspace is **active**, every form you open is already filled with this context — those fields do not even appear; a small banner shows the context instead. You type only what is new: the person's name, the harvest quantity, the loan amount.

**Everything you can do with workspaces:**

- **＋ New workspace** — choose Project → District, then optionally Block → GP → Village (each list narrows the next; **Village is optional** — leave it blank to pick the village per record instead). Name it what you like — "Ganjam · Millet · Badagada" — or leave the name blank and the system names it sensibly.
- **⚡ Quick start** — instead of picking piece by piece, start from an HQ assignment: project and district pre-filled, you add the village.
- **Switch with one click** — every workspace is a pill at the top. Working two projects in one day? Two pills; click to switch. There is no limit — keep one per village if you like.
- **✎ Rename / edit** and **✕ Remove** on each pill. Removing a workspace **never touches the records you entered with it** — it only removes the shortcut.
- **Pause** — enter something outside any context (forms open blank).
- **Your workspaces follow you.** They are saved centrally, so they appear on whatever computer you sign in from — and your supervisor can see who is working where (§11), which is a feature, not surveillance: it is how help gets sent where it is needed.
- **🎯 HQ Targets for this workspace** — the matching plan numbers (blocks, villages, beneficiaries) are shown on the workspace and inside the forms — so you always know what you are working toward.

**The entry cards** (New Beneficiary, Progress of Production & Output, Activity Progress, New SHG, New SHG Loan, Indicator Progress) sit below. They look dimmed until a workspace is active — a gentle reminder to set your context first (you *can* still open them without one; they simply will not be pre-filled). Each card shows a live count of records already in that section.

### 6.4 My Assignments

If HQ has assigned work to you (§6.2 step 4), a **📋 My assignments** panel appears with each work unit — project, district, and the beneficiary target. Press **Start ▸** and it becomes a ready workspace instantly. This is the complete loop: *HQ plans → assigns → you press one button → you are entering data in the right context.*

### 6.5 Entering each kind of record

Every form shares the same conveniences — learn them once, use them everywhere:

- **The ID makes itself.** BEN-0001, SHG-014… appear on save. You never invent numbers, and two colleagues saving at the same instant can never collide.
- **Every dropdown is searchable.** Click and type a few letters — no scrolling through hundreds of names.
- **Choices fill other fields.** Pick a Project → its Donor fills in. Pick a Geography → District/Block/GP/Village fill in. Pick a Beneficiary (in Production) → their whole context arrives. This is the chain saving you typing at every step.
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

Master Data is set up early and touched rarely — but understanding it explains why the rest of the system behaves as it does. **Set-up order matters** because of the chain: *Donors → Projects (naming their donor) → Geography (assigned to each project) → then daily data.*

> **There is ONE thematic area — Livelihood — so the system never asks for it.** It is filled in silently behind every record (from the project, which pins its theme). The Thematic Areas section, its objectives, and every thematic filter and dropdown are gone from the interface; the real planning unit is the **Project**. No data was deleted — existing thematic rows stay in the database and every historical link still resolves.

**Donors** — every funder, shown as cards: type badge (CSR / Foundation / Government…), contact person, email/phone, **approved budget**, and live counts of funded projects and reached beneficiaries. A card showing **"⚠ Not funding any project yet"** means this donor exists in the address book but the chain hasn't been connected — until it is, nothing can appear in that donor's reports. The card links you straight to the fix.

**Projects** — your delivery units. Key points:
- **"Funded by — Donor" is required.** You cannot create a project without naming who pays for it — this is the moment the chain is forged, and it is why every later record automatically knows its donor.
- **Objectives are added right on the form** — the golden **🎯 ＋ Add objective** button adds a row per objective (each with an optional beneficiary target); add as many as the project has and they are saved together with the project. When editing later, the same golden button opens the live objectives manager.
- **Status is automatic** — Planned / Ongoing / Completed derives from the start and end dates you enter. No one "forgets" to update a status.
- The table's **Donor / Funding column** shows each project's funder as a chip — or a loud **⚠ Unmapped** badge for older projects created before this rule. A banner at the top counts them and links to the repair (§15).

**Donor Mappings** — the ledger of who-funds-what, with the funded component, budget head, and approved amount. Mostly it maintains itself (created automatically when projects are made); you come here to fix ⚠-flagged projects or to record funding details. In its project picker, unlinked projects are marked **"⚠ needs donor"** so they leap out.

**Geography** — your places: Country → State → District → Block → Gram Panchayat → Village/Ward. Country pre-fills as *India*; the form remembers the last State/District you used, and the golden **🌍 ＋ Add another place** button adds row after row (Block · GP · Village) that all save together in ONE go — ten blocks of a district in a single Save, each auto-linked to the project. Every beneficiary, SHG and record points into this tree — which is what makes district-wise and village-wise reports possible.

**Village Demographics** — the profile of each village: households, population, categories, the contact person. Recording total households is what lets the system compute **Coverage %** — how much of a village your project actually reaches — a number donors love.

---

## 8. Working with field data

**Beneficiaries** — the most important form in the system. What its parts mean:

- **Registration Date** — arrives pre-filled with **today** (change it when registering backlog). It can never be left empty and can never be in the future, because this date is how beneficiaries are counted in "From–To" reports. A person with no date would silently vanish from every period report — the system simply does not allow that to happen.
- **Identity** — name, father/spouse, gender, age (0–120), social order (SC/ST/OBC/General), religion — these power the inclusion charts and breakdowns donors require.
- **Contact number** — must be a real 10-digit Indian mobile; +91 or a leading 0 are accepted and cleaned automatically.
- **Geography** — pick the village; district/block/GP fill themselves.
- **Land & livelihood** — landholding and income source classifications. **Total Land (acres) adds itself up** from the paddy/millet/vegetable areas you type in the baseline section (you can still overtype it if the household holds other land).
- **Organic Farming (Yes / No)** — required. This is the "before" half of the organic-farming indicator; the "after" comes from the production records.
- **Baseline livelihood — one table, not a wall of boxes.** Every income source is a row, and **these eleven rows are the only livelihood categories the whole system knows**: the seven **crops** (Paddy, Millet, Vegetable, Tuber Crop, Pulses, Oilseeds, Mushroom Cultivation) with *Area · Production kg · Income · Expenditure*, then the four **non-farm livelihoods** (Goat Rearing, Backyard Poultry, Micro Enterprise, Other Income) with *Income · Expenditure* only. Fill only what the household actually had.
  - Mushroom Cultivation counts as a crop row — it carries Area and Production (kg) too.
  - **Production & Output offers exactly these eleven and nothing else.** There is no second list of livelihoods anywhere in the software, so a household can never be given a category at registration that does not exist when their production is recorded, and no report has to guess which is which.
  - **Net is calculated for you** on every row and in the TOTAL line (income − expenditure) — it is never typed and never stored, so the two figures can never disagree.
  - The **Total income must equal the Annual Income at Registration**, and Total Land fills itself from the crop areas.
  - Every source flows into the income views, the dashboard Before-vs-After chart and the Income Impact report. **Take two extra minutes here** — this baseline is the "before" in every Before-vs-After income chart and in the Avg. Income Change headline. Without it, impact cannot be shown for this person.
- Each beneficiary row later offers an **income view** — the household's *complete* history in one table: the activities down the side, **Baseline** and then **every year that has production** across the top. Each year cell carries that year's income for that activity and, beneath it, the change against the baseline for *the same activity*, in ₹ and in %. A **TOTAL / year** line does the same for the household. This view always shows the full history and is deliberately not narrowed by the list's year filter.
  - **Years are never added together.** The baseline is a single year's income, so each year is compared with it on its own. Summing Year 1 + Year 2 + Year 3 and setting that against one year's baseline would invent growth that never happened.

**Production & Output Progress** — the actuals against HQ's output targets. Pick the beneficiary (the picker can be narrowed by village) and their context fills in; then record the livelihood, the year, the season and the money (a loss — negative profit — is allowed, because a loss is real data). These records feed the income trend and the outputs sheets of every report.

**Livelihood / Crop is required, and its list is the beneficiary baseline's list** — the same eleven categories, nothing else. If you open an older record saved under a category the list no longer offers (Fruits, Health Camp, Road Safety Training and the like), the original wording is **kept and clearly marked**, counted under *Other Income*, and you can bring it in line by choosing one of the eleven.

**How is the income recorded? — two honest ways, not one forced formula:**

| Mode | For | What you type |
| :-- | :-- | :-- |
| **Production (output × rate)** | The weighed crops: Paddy, Millet, Vegetable, Tuber Crop, Pulses, Oilseeds, Mushroom Cultivation | Output Quantity (kg) and Rate (₹/kg). **Income = Output × Rate** fills itself in. |
| **Direct income** | Goat Rearing, Backyard Poultry, Micro Enterprise, Other Income | The rupee figure for the year, for example **₹25,000**. Output Quantity and Rate are **put away entirely**. |

Choosing a category sets the mode for you, and you can change it whenever the real situation differs — a household that only knows the rupees from their paddy can use Direct income too. **Nobody is ever asked to invent a weight or a price per kilogram just to make a total appear.** A direct figure counts towards that household's income exactly like any other record.

**Net Profit = Income − Expenditure** in both modes, and fills itself in as you type. Type your own figure in any box and that box stops auto-filling.

Three questions only apply to something that is actually grown, so they are **not asked for the four non-farm livelihoods**: *Produce sold?*, *Food security throughout the year?* and *Organic Farming* (the "after" half of the organic indicator, required for every crop record).

> **Income figures are NET everywhere.** Every comparison — dashboard, banners, the income view, reports — uses what the household actually keeps (income − expenditure), on both the baseline and the production side. Records entered before Net Profit existed fall back to their gross income, so nothing breaks.

Two things make this list donor-proof:
- **A Livelihood or Season filter is required before anything displays.** One beneficiary legitimately has several production rows (Paddy · Kharif, Millet · Rabi, a goat batch…) — the filter shows each person once per view, so nobody ever *looks* duplicated. The records themselves stay together as one person's story; this is display only, and reports are unaffected. The crop & season filters combine with the thematic/project/donor chain like every other filter.
- **An Income Change column** (₹ and %) sits beside Net Profit: the beneficiary's cumulative production income across ALL their records versus their total baseline — the same honest figure on every row of that person, and in the section's Excel export too.

**Activity Progress** — delivery tracked year by year in one compact table. Set the activity's total target, then fill each year's row: its target and the **Q1–Q4** achievements (or flip the switch to **Monthly** and type the 12 months — they roll up into the quarters automatically). **＋ Add Year** extends the table up to six years. Each year's achievement and the cumulative total **calculate themselves**, and a live progress bar shows the % achieved against the total target — always arithmetic, never opinion. From the field-office page, the *Activity Progress* card opens this full section (filters, search, **✎ Update progress** on each row, and ＋ New activity) so progress is updated on the existing activity rather than re-created. **The target boxes — and adding or deleting activities — need the 🎯 Targets right (§12):** without it the plan is read-only 🔒 and only the achievements can be typed.

On the list, the **Year / Quarter switcher** in the filter bar changes what the columns show: the overview shows Year 1; pick *Year 3* and the table shows Y3's target and achievement; add *Q2* and that quarter's figure appears too — every year you entered is one click away, with no blank Y2–Y6 columns cluttering the default view (the quarter picker unlocks only after a year is chosen).

---

## 9. Self-Help Groups and Loans

**Self-Help Groups** — each SHG with its village, project (and through it, donor and thematic area), members, roles and savings. The membership and savings figures roll up into the Dashboard and reports.

**SHG Loans** — the list shows each loan's **Village and Block** (pulled from its SHG), so a loan is placeable at a glance. Recording one takes seconds *because of the chain*:
1. Optionally narrow with **🔎 Find SHG by Project**.
2. **Pick the SHG.** That's it — the loan inherits the SHG's donor, thematic area and project silently (you won't even see those fields; there is nothing to get wrong).
3. Enter the loan's own facts: financial year, amount, source (bank linkage…), purpose, date received (never in the future), repayment status.

Repayment status appears as a colour badge in the table, and loans-by-financial-year is a standard sheet in the reports.

---

## 9b. Local Governance — Gram Sabha & Convergence

Two sections capture the governance side of the work:

Local Governance is **one section with two tabs**, matching the client's Convergence sheet.

**🏛 Gram Sabha / VDC tab** — one record per meeting: District → Village, the VDC name, the date, **Male + Female participants (Total adds itself up)** and whether the **VDP was submitted at the Gram Sabha (Yes/No)**. Female participation feeds the women-in-governance indicator automatically.

**💰 Convergence — Govt Schemes tab** — every scheme leveraged: the **Department** (official government list), the scheme's name, type of work, **households benefited** and the **Amount Mobilised (₹)**. These records power two logged indicators: households accessing government schemes, and the total amount leveraged.

Both work exactly like every other section — workspace auto-fill, filters, search, Excel export and bulk import.

---

## 10. Logged Indicators (M&E)

**The M&E section is now the organisation's 11 fixed indicators — and nothing on that page is typed.** Every value is computed by the server from data already entered elsewhere in the MIS, using the exact formulas agreed with the client. There is no free-form indicator entry any more: the yardsticks cannot be invented, edited or argued with.

| # | Indicator | Computed from |
|---|---|---|
| 1 | % increase in annual household income | (Avg current NET income/yr − Avg baseline NET) ÷ Avg baseline × 100 — matched pairs |
| 2 | % households with improved food security | "Food security throughout the year?" on Production records |
| 3 | % increase in agricultural productivity | Current paddy+millet kg vs baseline paddy+millet kg |
| 4 | % increase in area under improved practices | Current cultivated area vs baseline crop area |
| 5 | % farmers practising organic / natural farming | Organic Yes now (production) vs organic Yes at baseline |
| 6 | % households with diversified livelihoods | Households cultivating **≥ 3 distinct crops** |
| 7 | % households adopting alternative livelihoods | Alternative Livelihood recorded on Beneficiary / Production |
| 8 | % SHG members accessing formal finance | Members of SHGs that received loans ÷ all SHG members |
| 9 | % women participating in local governance | Female ÷ total Gram Sabha participants |
| 10 | % households accessing government schemes | Households benefited (Convergence) ÷ total households |
| 11 | Amount leveraged through govt schemes (₹) | Σ Amount Mobilised (Convergence) |

**How to read a card:** the number, then the underlying figures in plain words ("Avg baseline NET ₹42,000 → avg current NET ₹61,500/yr · 128 beneficiaries with production logged"), then the exact formula. **An indicator whose source data is not entered yet says "waiting for data" — it never shows a made-up number**, and the detail line tells you exactly which field to fill to light it up.

The three filters (Thematic / Project / Donor) use the same smart chain as the rest of the MIS, and **⬇ Excel** downloads the whole table with formulas and underlying figures — donor-ready.

> **These 11 are now the only indicators in the system.** The old free-form Indicators and Indicator Progress sections are fully retired — they are gone from the menu and from the permission matrix, and any old link or bookmark lands here instead. **No data was deleted:** every historical row is still in the database, still visible in the Audit Trail, and still included in exports — it is simply no longer editable, so the yardsticks can never be quietly changed.

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
- **📌 Project access — pin a user to specific project(s).** Above the matrix, tick the projects a user may work on. Tick **nothing** and they see all projects (normal for HQ). Tick one or more and that user's every list, dropdown and report narrows to those projects — and **the server refuses any record belonging to another project**, whether created, edited, deleted or bulk-imported, even from a tampered request. This is how a data-entry officer is confined to their own project without needing a separate login for anything else.
- **🎯 Targets — the one row that protects your plan.** Every planned number in the system — activity targets (total and per-year), indicator baselines & targets, HQ plan rows, a project's target beneficiaries — is **locked for everyone** until you tick *Edit* on the **🎯 Targets** row (admins always can). Without it, a person can still update achievements and progress against those targets, but the target boxes are read-only 🔒, they cannot add or delete activities/indicators (a new one would carry new targets), and the server refuses target changes even from a tampered request. This is deliberate: the field reports *against* the plan; only HQ *sets* the plan.

**Preset buttons** give you a sound starting point in one click — then adjust any box:
- **👷 Field officer** — Field-Office Entry + the field data they enter. No HQ tab, no admin pages, no delete — and **no 🎯 Targets**: they update progress, never the plan.
- **🏢 HQ staff** — everything except Users & Access, **including 🎯 Targets**.
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

The system's memory — at full detail. Every meaningful event writes a line: record created / changed / deleted, sign-ins and sign-outs, failed logins and lockouts, imports, permission changes, password resets — each with **who, when (IST), from which address, device and browser tab**.

**How to read a line:** the person appears first as a **coloured name chip** (each user keeps their own colour, so one glance shows who did what), then the action badge (green create, amber edit, red delete…), the section, the record's ID and the plain-language message — edits even *say* which fields changed, right in the message.

**The full story of any event is one click away.** Events with data carry an expandable panel:
- an **edit** opens a *Before → After* table listing **every single field that changed**, old value struck through in red, new value in green — IDs shown with their real names (project, donor, place…);
- a **create** lists every value the record was born with;
- a **delete** lists everything the record contained at the moment it was removed — nothing disappears without a trace.

**Finding things fast:** the search box scans messages, names and IDs; the three dropdowns narrow by **action**, **user** and **section**; summary chips on top count events per action live. Days are grouped (Today / Yesterday / date) so scanning a week takes seconds. **⬇ Excel** downloads the filtered view — including the field-by-field changes — as a formatted sheet for management or donors.

**What you will never find here:** passwords or security tokens — they are scrubbed before logging, always.

*Why it exists:* accountability without arguments. "Who changed this target?" takes ten seconds to answer, with evidence.

---

## 14. Getting data out

Everything the system knows can leave it, formatted and presentable. Choosing the right export:

| You need… | Use |
|---|---|
| This section's table, nicely formatted | **Export ▾ → Current view (all rows)** — styled headers, banded rows, frozen header row, filters on, ₹ formats, totals row |
| The same, one sheet per project / donor | **Export ▾ → Split by Project / Donor** |
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

*VIEWS MIS — built from the ground up for your programme. Keep this guide beside the system; it describes exactly what every screen does and why.*

---

## 17. Messages and errors you may see

Every message the system can show is listed here with its meaning and what to do. Most of them are the system protecting your data rather than something being broken.

### Signing in

| Message | What it means and what to do |
|---|---|
| Invalid username or password | The details do not match. Check spelling and keyboard case. If it still fails, ask your administrator to reset the password. |
| Too many failed attempts, account locked | The account was protected after several wrong passwords. Wait a few minutes, or ask your administrator to reset it. |
| Session expired, please sign in again | You were idle for a long time, or the same account signed in elsewhere. Sign in again. Unsaved typing must be re-entered. |
| Login failed, please run the upgrade file | The database has not been upgraded for this version. The administrator runs the files in the sql folder once in phpMyAdmin. |

### Connection and server

| Message | What it means and what to do |
|---|---|
| Could not reach the server | Your device could not contact the server, almost always the internet connection. Check it and try again. Reads are retried automatically first. |
| The server took too long to respond | The request was abandoned after thirty seconds. Try again. If it repeats, the connection is very slow or the server is busy. |
| Bad server response | The server replied with something unreadable. Try again, and inform your administrator if it repeats. |
| Save failed | The record could not be written. Nothing was saved, so no partial record exists. Try again and inform your administrator if it repeats. |
| Could not load a list | A reference list such as projects or donors did not load. The system retries on your next action. Refresh the page if dropdowns stay empty. |
| Some lists failed to load, retrying on your next click | A temporary connection problem. Avoid saving until the dropdowns fill, so the correct links are recorded. |
| A required table or column is missing | The database is behind this version of the application. The administrator runs the sql upgrade files once. No data is lost by running them. |

### Permissions

| Message | What it means and what to do |
|---|---|
| You do not have permission to view this section | Ask your administrator to tick the View box for it in Users and Access. |
| You do not have permission to add, edit or delete records | You can see the section but not change it. Ask for the Edit or Delete box on that row. |
| You are assigned to specific projects | Your account is limited to certain projects, and this record belongs to another one. Choose one of your projects or ask for wider access. |
| This record belongs to a project you are not assigned to | The same restriction, seen when editing or deleting. |
| Targets are locked, only target setters can change them | Targets are set by head office. You may record achievements. Ask for the Targets permission if you need to change targets. |
| New activities and their targets are set by head office | Creating an activity creates targets, so it needs the Targets permission. You can still update existing activities. |
| Only an admin can create, edit or delete admin accounts | Administrator accounts are managed only by another administrator. |
| Only a root admin can modify a root protected account | Reserved for the most senior administrators. |
| The root admin cannot be deleted | The system always keeps at least one root administrator so the organisation cannot lock itself out. |
| You cannot delete yourself | Ask another administrator to remove your account. |

### Required fields and validation

| Message | What it means and what to do |
|---|---|
| Select the Donor funding this project | A project cannot exist without naming who pays for it. Choose the donor, or create the donor first. |
| Select the Project this record belongs to | The record needs a project to reach the dashboard and reports. |
| Please answer Organic Farming, Yes or No | Required, because it produces the organic farming indicator. It is not asked for Goat Rearing, Backyard Poultry, Micro Enterprise or Other Income, since nothing is grown in those. |
| Please choose the Project Year | Every production record must state its year, otherwise it sits outside every year filter and every year-by-year comparison. |
| Please choose the Livelihood / Crop | The category is required and must be one of the same eleven the beneficiary baseline uses. |
| Please choose how the income is recorded | Pick *Production (output × rate)* for a weighed crop, or *Direct income* for a livelihood with no kilograms and no price per kilogram. |
| Direct income was chosen, so please type the Income (₹) | You chose to enter the figure yourself, so the rupee amount for the year is required. |
| Please enter the Output Quantity and Rate, or switch to Direct income | In Production mode the income comes from a quantity and a rate. Fill both, type the income yourself, or switch the mode. |
| Baseline income total must equal Annual Income at Registration | The baseline rows and the annual income figure disagree. Correct one of them. |
| Expenditure looks far larger than its income | Usually an extra zero. Check the figure. |
| Age must be between 0 and 120 | Check the age entered. |
| Contact number must be a valid ten digit Indian mobile | Ten digits beginning with 6, 7, 8 or 9. A leading zero or country code is removed automatically. |
| End Date cannot be before Start Date | Check the two dates. |
| A date cannot be in the future | Registration, joining and loan dates must be today or earlier, otherwise the record would vanish from period reports. |
| Reporting Year should be like 2025 or 2025-26 | Only a four digit year or the Indian financial year format is accepted. |
| Password must be at least six characters, and passwords do not match | Choose a longer password and type the same value twice. |

### Duplicates and data integrity

| Message | What it means and what to do |
|---|---|
| Duplicate value, a record with this key already exists | The database refused a second record with the same identity code, protecting reports from double counting. |
| Possible duplicate, save anyway | A warning, not a block. A very similar record exists. Check it, and continue only if this is genuinely separate. |
| Duplicate donor identity code | Two donors share one code so their figures would mix. Keep one, move the details across, delete the other, and create it again. |
| A value was missing or in the wrong format | Usually a number field that received text. Check the highlighted field. |

### Importing from Excel

| Message | What it means and what to do |
|---|---|
| The file has no data rows | The sheet has headings but no records. Check that the correct sheet was selected. |
| Bulk import failed, or some rows were skipped | Rejected rows are listed in a downloadable Excel file with the reason for each. Correct them, delete the reason column, and import again. Accepted rows are already saved. |

### Messages that look like errors but are not

| Message | Why it appears |
|---|---|
| Pick a Crop or a Season to display the records | Production records appear only after a crop or season is chosen, so one person is never mistaken for a duplicate. |
| Waiting for data on an indicator card | The indicator has no source data yet. The card names the field to fill. The system will not invent a number. |
| Projects or donors not linked in Donor Mapping | Those records cannot appear when filtering from the other side. Select Map now to correct the link. |
| No geographies assigned to this project yet | The project has no places attached, so district and village lists are empty. Assign them under Projects, then Geographies. |
| A negative variance, or a fall in income | Under achievement and reduced income are real results. The system reports them honestly. |
| You have unsaved changes on this form | You tried to leave a form with unsaved typing. Stay and save, or leave and lose those changes. |
| Excel engine still loading | The spreadsheet component has not finished loading. Wait a moment and select the download again. |

---

**This platform was built by PlanEx Hivers Consulting Private Limited.**

**All rights reserved 2026.**
