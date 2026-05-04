# AgentForge Clinical Co-Pilot — User definition and use cases

You’re standing in the hall between rooms. The schedule says “Smith, 10:15.” You have maybe ninety seconds to remember *who*, *why today*, *what moved since last time*, and *what not to miss*—without opening five tabs in OpenEMR.

That moment is what this module is for. **This file** is the map: every capability in [ARCHITECTURE.md](ARCHITECTURE.md) must trace to a named use case below. Anything that doesn’t map stays out until we update this file on purpose.

**PRD anchor:** AgentForge Week 1 intent lives in [`.cursor/rules/agentforge-clinical-copilot-requirements.mdc`](.cursor/rules/agentforge-clinical-copilot-requirements.mdc). Repo setup and contribution norms stay in [CONTRIBUTING.md](CONTRIBUTING.md) and the root READMEs—I don’t restate them here.

**Honest scope split:** Three use cases below are **implemented** in the current fork (they match the three server tools and the patient-summary card). Four are **forward**—documented so reviewers can see the path to “7 clear use cases” without pretending they ship today.

---

## Target User

**Role:** Board-certified **primary care physician** (family medicine or general internal medicine)—narrow on purpose, not “every clinician.”

**Setting:** Community **outpatient clinic**, high volume (roughly fifteen to twenty-five face-to-face visits per day), mixed acute and chronic care, plus inbox and results between visits.

**Technical context:** OpenEMR for scheduling, charting, e-prescribing, labs/imaging. The Week 1 **public demo** runs on **[Cloud Clusters](https://www.cloudclusters.io/cloud/openemr)** managed OpenEMR Docker; product thinking still assumes a normal clinic deployment. Between rooms you get **about a minute** to re-orient—not time to read the whole chart.

**Goals:** Lower cognitive load, don’t miss big deltas since last visit, walk in with a correct mental model.

**Hard no:** Unreliable “facts,” buried uncertainty, or a briefing that costs more than a few seconds of attention before the knock on the door.

---

## Day-in-the-life workflow (where the agent appears)

1. **Morning:** Schedule, complex visits, open tasks.
2. **Between rooms:** Active patient open; you need a **fast briefing** (who, why today, what changed, what to verify).
3. **In the room:** Confirm with the patient; document; reconcile meds and problems.
4. **After block:** Close loops (orders, referrals, messaging).

The card sits in **step 2** first. Follow-up turns matter after you’ve glanced at something specific in the chart.

---

## User interface entry point (current implementation)

**Implemented surface:** A **Clinical Co-Pilot** card on the **patient summary / dashboard**—same OpenEMR login and `pid` as the rest of the chart. Requests are **same-origin POST** with CSRF to server-side PHP (`public/copilot_request.php` → `CopilotRequestController`). No separate SPA holding PHI outside the session.

---

## Use cases

Each case says **why chat beats a static widget** for this user (per the PRD rule file). Capabilities that can’t tie to a row below don’t ship until this doc changes.

### Use case 1 — “Who am I seeing next, and why today?” **(implemented)**

**Moment:** Thirty to ninety seconds before you enter the room.

**Need:** A short story: today’s complaint if it’s documented, relevant problems, and the **purpose of this visit** without reading every old note.

**Why conversational:** The first question is natural language (“What’s the one-liner?”); the second often depends on what you saw (“What did we do last time for the same thing?”). A fixed widget can’t branch without pre-building every drill path.

**What demo data taught me:** In `RecentEncountersTool`, `reason` often comes back **empty** even when the row is real. That pushed UC1 toward **uncertainty in the answer**, not a fake “chief complaint.” If today’s reason isn’t in the chart, the agent should say so—not invent one.

**Agent boundaries:** Grounded answers with **citations** ([ARCHITECTURE.md](ARCHITECTURE.md) verification). Missing visit reason = stated gap, not a guess.

---

### Use case 2 — “What changed since I last saw them?” **(implemented)**

**Moment:** Same gap, or first open on a patient you haven’t seen in months.

**Need:** Deltas that matter in primary care: new labs (when in procedure tables), med/problem/allergy list context, recent encounter metadata—not a raw firehose.

**Why conversational:** “What changed?” is underspecified; the follow-up depends on what exists (“New A1c?” “Did cardiology change the beta blocker?”). That’s faster than clicking through modules.

**Agent boundaries:** Only deltas backed by tool JSON; separate **“not in chart”** from **“nothing new in what we loaded.”**

---

### Use case 3 — “What should I double-check before I walk in?” **(implemented)**

**Moment:** Right before rooming—polypharmacy, recent churn, abnormal labs.

**Need:** A **short checklist** to verify verbally or re-open in the record.

**Why conversational:** Risk-tailored follow-ups (“Anything pregnancy-adjacent?”) beat a one-size template.

**Concrete tie to code:** `ClinicalDomainRules` counts meds from `chart_lists.medications`; at **12+** it adds an uncertainty line about polypharmacy and strips med *instruction* language unless citations hit a `medications` path. That’s UC3 in code, not in marketing copy.

**Agent boundaries:** Suggestions + citations only. No autonomous dosing or orders—domain rules strip dosing/imperative patterns regardless of model enthusiasm.

---

### Use case 4 — Care-gap / overdue surfacing **(forward)**

**Moment:** Same inter-visit window when you’re asking “what did we drop?”

**Need:** Overdue screenings or follow-ups **when the data exists** in structured form.

**Why conversational:** The right gap depends on age, problems, and what’s already ordered—underspecified without dialogue.

**Forward implementation sketch:** A tool such as **`get_pending_results`** (proposed name in [ARCHITECTURE.md](ARCHITECTURE.md)) for bounded pending / incomplete result linkage, with citations under a new merge root (e.g. `pending_results.*`). **Not in `ToolRegistry` today.**

---

### Use case 5 — Med-reconciliation prep **(forward)**

**Moment:** Before or after the visit when you’re about to reconcile the list.

**Need:** “What moved on meds since last encounter?” with explicit list deltas where the schema supports it.

**Why conversational:** You drill from summary to “what changed on lisinopril?” in one or two turns.

**Forward sketch:** Reuse `get_chart_lists` plus **`get_active_orders`** (proposed name in [ARCHITECTURE.md](ARCHITECTURE.md)) for a bounded active-order slice and stable citation paths. **Not implemented today.**

---

### Use case 6 — High-risk visit pre-flight **(forward)**

**Moment:** Complex patient—polypharmacy, possible ED bounce-back, lab flags.

**Need:** One composite read: meds count + recent encounters + recent labs in one briefing pass.

**Why conversational:** You might ask “anything ED-related?” only after seeing encounter categories.

**Note:** This can be **orchestration over existing tools** once prompts and evals catch edge cases; it may not need a net-new tool if UC1–UC3 payloads stay bounded.

---

### Use case 7 — Result triage at the inbox **(forward)**

**Moment:** Inbox / results queue—not the patient card between rooms.

**Need:** Triage “what needs eyes first” with record links.

**Why conversational:** Sorting and drill-down vary by day.

**Explicit deferral:** Different **surface and trust model** than session-scoped patient dashboard chat. Out of scope for the current card until we define inbox authz and audit the same way we did for chart read tools ([AUDIT.md](AUDIT.md) F2).

---

## What I learned from real OpenEMR data (that changed the design)

**Encounters don’t guarantee a visit “reason” string.** `EncounterService::getEncountersForPatientByPid` feeds `RecentEncountersTool`; empty `reason` is common. UC1 became about **labeling uncertainty**, not narrating a fantasy chief complaint.

**Allergies, meds, and problems share the same table shape.** `ChartContextTool` / `ChartListsTool` pull from OpenEMR `lists` (and related) by type. One tool surface for “chart lists” was enough; I didn’t split three tools for three list types because the merge root is already one coherent JSON object.

**Labs are a three-hop join, not one table.** `procedure_result` → `procedure_report` → `procedure_order` is what `RecentLabsTool` queries. Getting `patient_id` wrong on the join would silently return nothing—that’s why the SQL is explicit and capped, and why “no rows” is a first-class outcome (`note: no_rows`).

**Lab read is ACL-gated inside the tool.** `RecentLabsTool` checks `AclMain::aclCheckCore('patients', 'lab')`. The controller also gates on `patients` / `demo`. Defense in depth: if lab ACL fails, you still get chart lists and encounters without pretending you saw labs.

**Polypharmacy isn’t a vibe—it’s a count.** Twelve medications from `chart_lists.medications` flips behavior in `ClinicalDomainRules`. That number is arbitrary but *testable*; it’s easier to tune than “when the model sounds worried.”

---

## Traceability matrix

| UC | Status | Minimum data domains | Primary risk if wrong | Mitigation |
|----|--------|----------------------|------------------------|------------|
| UC1 Visit framing | **Implemented** | Encounters, problems (via chart lists) | Hallucinated visit reason | Citations + `VerificationGate`; uncertainties for missing reason |
| UC2 Deltas | **Implemented** | Labs (procedure tables), meds/allergies/problems, encounters | False “no change” / missed result | Tool-only facts; empty labs array + notes |
| UC3 Pre-room checks | **Implemented** | Meds, encounters, labs | Unsafe recommendation | `ClinicalDomainRules` + verify-in-room copy |
| UC4 Care gaps | **Forward** | Rules / orders / problems (TBD) | Wrong gap or invented due date | `get_pending_results` (proposed) + citations + rule layer |
| UC5 Med reconciliation | **Forward** | Med history vs encounter timeline (TBD) | Wrong delta | `get_active_orders` (proposed) + `chart_lists` + verification |
| UC6 High-risk pre-flight | **Forward** | Composite of UC1–UC3 data | Overconfident composite | Same gates; stricter eval on composites |
| UC7 Inbox triage | **Forward** | Non–patient-card surface (TBD) | Wrong patient / wrong priority | Separate UX + ACL audit before build |

---

## Out of scope (until this doc changes)

- **Autonomous orders** or chart write-back without human action—anything that **mutates** the record is out until we define a different safety bar.
- **Cross-patient** analytics or population health.
- **Generic medical chat** off the active patient session.

---

## Document control

| Field | Value |
|--------|--------|
| **Project** | AgentForge — Clinical Co-Pilot |
| **Upstream fork** | [openemr/openemr](https://github.com/openemr/openemr) (`master`) |
| **Companion documents** | [AUDIT.md](AUDIT.md), [ARCHITECTURE.md](ARCHITECTURE.md) |
| **PRD** | [`.cursor/rules/agentforge-clinical-copilot-requirements.mdc`](.cursor/rules/agentforge-clinical-copilot-requirements.mdc) |
