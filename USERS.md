# AgentForge Clinical Co-Pilot — User definition and use cases

This document is the **source of truth** for *who* the Clinical Co-Pilot serves and *which problems* it solves. Every agent capability in implementation and in [ARCHITECTURE.md](ARCHITECTURE.md) must trace to a use case listed here.

**Agent surface area (PRD):** Features that do not map to a use case below—including extra tools, multi-turn flows, or “nice to have” chat—are **out of scope** until this document is updated deliberately. The bar from the Week 1 PRD is whether a **narrow, real** user would **choose** this agent shape over a dashboard or better chart navigation.

---

## Target user

**Role:** A **deliberately narrow** persona for Week 1: board-certified **primary care physician** (family medicine or general internal medicine)—not “all clinicians” or a generic “physician needs information” thesis.

**Setting:** Community **outpatient clinic** with a **high-volume schedule** (approximately fifteen to twenty-five face-to-face visits per day), mixed acute and chronic care, plus inbox and results tasks between visits.

**Technical context:** The physician already uses **OpenEMR** for scheduling, charting, e-prescribing, and lab/imaging review; for this Week 1 project, the **public demo** OpenEMR instance is hosted on **[Cloud Clusters](https://www.cloudclusters.io/cloud/openemr)** managed **OpenEMR Docker** (a pattern many SMB practices already use and trust), while day-to-day product thinking still assumes a normal clinic deployment of OpenEMR. They move between exam rooms with **roughly one minute or less** between patients to re-orient on the next chart.

**Goals:** Minimize cognitive load, avoid missing important changes since the last visit, and enter the room with an accurate mental model without reading the entire chart.

**Constraints:** They will **not** adopt a tool that adds unreliable “facts,” hides uncertainty, or slows the workflow beyond a few seconds for the initial briefing.

---

## Day-in-the-life workflow (where the agent appears)

1. **Morning:** Review schedule; identify complex visits; skim open tasks.
2. **Between rooms (critical window):** Open the **active patient** in OpenEMR; need a **fast briefing** (who, why today, what changed, what to verify in the room).
3. **In the room:** Confirm with the patient; document; reconcile meds and problems.
4. **After block:** Close loops (orders, referrals, messaging).

The agent is positioned primarily in **step 2**—the **inter-visit gap**—with optional short follow-up questions after the physician has seen a specific data item in the chart.

---

## User interface entry point (sprint scope)

**Planned surface:** A **dedicated “Clinical Co-Pilot” panel** within the existing OpenEMR **patient-centric** workflow—for example a **tab or slide-out** on the **patient summary / demographics** context or the **encounter** screen—implemented as **new additive UI** that posts to **server-side PHP** endpoints only. No standalone SPA that holds PHI outside the authenticated OpenEMR session.

*(Exact screen name may match your fork’s first implementation; the requirement is: same login session and patient selection as the rest of the chart.)*

---

## Use cases

Each use case below includes **why a conversational agent** is appropriate (per AgentForge PRD), not merely “because AI is available.” If a capability cannot cite one of these use cases and justify **multi-turn** or **tool** behavior against that need, it should not ship in the sprint.

### Use case 1 — “Who am I seeing next, and why today?”

**Moment:** Between rooms, **thirty to ninety seconds** before entering the next visit.

**Need:** A **coherent narrative**: chief complaint for today (if documented), recent relevant diagnoses, and the **purpose of this visit** without reading every historical note.

**Why conversational (not only a dashboard):** The physician’s question is **naturally phrased** (“What’s the one-liner on this visit?”) and may **branch** (“What did we do last time for the same complaint?”). A static widget cannot answer **follow-ups** without pre-building every drill path. Multi-turn dialogue matches **exploratory recall** under time pressure.

**Agent boundaries:** Answers must be **grounded in chart data** with **explicit citations** to sources (see [ARCHITECTURE.md](ARCHITECTURE.md) verification). If today’s visit reason is missing, the agent **states the gap** instead of inventing a reason.

---

### Use case 2 — “What changed since I last saw them?”

**Moment:** Same inter-visit window, or **first click** when opening a patient with a long interval since last visit.

**Need:** **Delta-oriented** summary: new labs or imaging since last encounter, med list changes, new outside records if present, new allergies or problems—**prioritized** by clinical relevance for primary care (not a raw feed).

**Why conversational:** “What changed?” is **underspecified**; the follow-up depends on what the chart contains (“Any new A1c?” “Did cardiology change their beta blocker?”). Conversation supports **progressive refinement** faster than clicking through five modules.

**Agent boundaries:** Only report deltas **supported by structured or cited narrative data**; distinguish **“not in chart”** from **“unchanged.”**

---

### Use case 3 — “What should I double-check before I walk in?”

**Moment:** Immediately before rooming, for **high-risk** or **complex** patients (polypharmacy, recent ED visit, abnormal trending labs).

**Need:** A **short checklist** of items to verbally verify with the patient or to re-check in the record (e.g. adherence-sensitive meds, pending results not reviewed, care gaps).

**Why conversational:** The physician may ask **risk-tailored** questions (“Anything pregnancy-related?” “Any red-flag symptoms documented?”) that vary by patient; a fixed checklist template **over- or under-shoots**. The agent proposes **contextual prompts** the user can accept or dismiss in one or two turns.

**Agent boundaries:** Items are **suggestions tied to citations**, not autonomous clinical decisions. Any **drug interaction or dosing “flag”** must follow the project’s **domain-constraint** rules in architecture (rules engine / hard rejects), not model improvisation.

---

## Traceability matrix (for implementation planning)

| Use case | Minimum data domains | Primary risk if wrong | Mitigation (architecture) |
|----------|----------------------|------------------------|---------------------------|
| UC1 Visit framing | Encounters, problem list, recent notes | Hallucinated visit reason | Structured citations + PHP verification gate |
| UC2 Deltas | Labs, meds, allergies, key vitals | False “no change” / missed new result | Tool-backed facts only + uncertainty labels |
| UC3 Pre-room checks | Meds, recent encounters, flags | Unsafe recommendation | Rule layer + “verify in room” phrasing |

---

## Out of scope (for the sprint)

- **Autonomous orders** or documentation without human action.
- **Cross-patient** analytics or population health (different user and trust model).
- **Generic medical chat** not tied to the **active patient’s** record.

---

## Document control

| Field | Value |
|--------|--------|
| **Project** | AgentForge — Clinical Co-Pilot |
| **Upstream fork** | [openemr/openemr](https://github.com/openemr/openemr) (`master`) |
| **Companion documents** | [AUDIT.md](AUDIT.md), [ARCHITECTURE.md](ARCHITECTURE.md) |
| **PRD alignment** | [ARCHITECTURE.md](ARCHITECTURE.md) must trace capabilities here; [AUDIT.md](AUDIT.md) informs integration risks. |
