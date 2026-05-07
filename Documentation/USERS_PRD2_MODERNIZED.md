# ============================================================================
# USERS_PRD2_MODERNIZED.md
# ============================================================================
# Project:
#   OpenEMR Patient Dashboard Modernization (Presentation Layer)
#
# Repository:
#   https://github.com/monigarr/openemr/tree/prd2_af_modernized
#
# Version:
#   0.1.0
#
# Status:
#   Active — Living document, updated as user feedback is received
#
# Authors:
#   Monica Peters (Human Lead)
#
# Created:
#   2026-05-06
#
# Last Updated:
#   2026-05-06
#
# Classification:
#   Internal — Contains user personas, workflow descriptions, and clinical context
#
# ============================================================================
#
# PURPOSE
# ----------------------------------------------------------------------------
# Comprehensive user analysis for the OpenEMR Patient Dashboard modernization.
# This document defines:
#
# - All user personas who interact with the Patient Dashboard
# - Their goals, workflows, pain points, and expectations
# - How the modernization addresses (or intentionally does not address) their needs
# - Accessibility requirements across user types
# - Clinical safety considerations from each user's perspective
# - Feature parity expectations: what "the same but better" means to each persona
#
# This document serves:
# - UX validation — ensuring the reimplementation serves real users, not abstractions
# - Onboarding — new team members understand who they're building for
# - Testing — user scenarios for Playwright E2E tests and manual QA
# - Defense — supporting the claim of "feature parity" by demonstrating understanding
#   of the original users' workflows
#
# Philosophy: A technically perfect port that frustrates its users is a failure.
# The modernization must feel familiar to existing users while enabling
# incremental UX improvements that were impractical in the PHP architecture.
#
# ============================================================================
```

---

# 1. User Persona Overview

The Patient Dashboard serves multiple distinct user types within a clinical setting. Each persona interacts with the dashboard differently, has different priorities, and will experience the modernization differently.

| Persona | Primary Role | Dashboard Usage Pattern | Clinical Authority |
| ------- | ------------ | ----------------------- | ------------------ |
| **Dr. Sarah — Primary Care Physician** | Diagnostician, treatment decision-maker | Rapid information scan, multiple patients per hour | Full — makes clinical decisions based on dashboard data |
| **Nurse Maria — Clinical Nurse** | Patient intake, vital signs, medication administration | Task-focused, looks for specific data points | Limited — acts on orders, escalates anomalies |
| **Dr. Chen — Specialist (Cardiologist)** | Consultation, focused clinical review | Deep dive into specific sections (medications, lab results) | Full within specialty — needs complete medication and lab context |
| **Alex — Medical Assistant** | Rooming patients, updating records, scheduling | Data entry verification, patient context for the visit | None — verifies and surfaces information |
| **Jordan — Clinic Administrator** | Operational oversight, quality reporting | Periodic review, audit preparation, workflow validation | None clinical — validates that systems are functioning |
| **Taylor — Front Desk / Reception** | Patient check-in, demographic verification | Patient header only — name, DOB, MRN confirmation | None — identity verification only |
| **Casey — IT / Health Informatics** | System maintenance, integration, support | Troubleshooting, verifying API connectivity, testing | None clinical — technical system ownership |

---

# 2. Primary Personas — Detailed

---

## 2.1 Dr. Sarah — Primary Care Physician

### Profile
- **Experience:** 12 years in family medicine
- **Technical Comfort:** Moderate — comfortable with EHRs, frustrated by slow interfaces
- **Daily Patient Load:** 18-24 patients
- **Time Per Patient Dashboard Interaction:** 30-90 seconds

### Goals When Using the Patient Dashboard
1. Rapidly assess the patient's current clinical picture before entering the exam room.
2. Verify allergies before prescribing or administering any medication.
3. Review active problem list to understand the patient's chronic conditions.
4. Confirm current medications and recent prescription history.
5. Identify who else on the care team has interacted with this patient.

### Current Pain Points (PHP Dashboard)
- **Full page reloads:** Every sort, filter, or section expansion triggers a server round-trip. During peak hours, this can be 3-5 seconds per interaction.
- **No background updates:** If a nurse adds an allergy while Dr. Sarah is viewing the dashboard, she must manually refresh to see it.
- **Lost context on navigation:** Clicking into a medication detail loses the patient header context. Must re-navigate back to the dashboard.
- **Inconsistent error handling:** If the allergies query fails, a partial page renders with no clear indication that data is missing.

### Expectations from the Modernization
- **Same layout, same information hierarchy.** She should not have to relearn where things are.
- **Faster interactions.** Sorting allergies should feel instant, not like a page load.
- **Clear loading states.** She wants to know the difference between "no allergies" and "allergies haven't loaded yet."
- **Persistent patient header.** As she scrolls through clinical cards, the patient identity bar must remain visible — she must never accidentally act on the wrong patient's data.

### Clinical Safety Criticality: HIGH
Dr. Sarah makes prescribing decisions based on the Allergies and Medications cards. Incorrect, stale, or missing data in these sections has direct patient safety implications.

### Feature Parity Standard for This Persona
The new dashboard is acceptable to Dr. Sarah if:
- [ ] She can locate the same information in the same visual positions as the PHP dashboard.
- [ ] No information from the PHP dashboard is missing.
- [ ] The interface responds to her interactions (clicks, scrolls) within 200ms.
- [ ] She can clearly distinguish "data loading," "no data," and "error loading data."

---

## 2.2 Nurse Maria — Clinical Nurse

### Profile
- **Experience:** 8 years in outpatient clinical nursing
- **Technical Comfort:** Moderate — task-oriented EHR user
- **Daily Patient Load:** Supports 3-4 physicians (indirect load: 50+ patients)
- **Time Per Patient Dashboard Interaction:** 15-45 seconds (focused, task-specific)

### Goals When Using the Patient Dashboard
1. Verify patient identity before administering medications or collecting specimens (Patient Header).
2. Review allergies before any medication administration or injection.
3. Check care team assignments to know which physician to contact with questions.
4. Review recent lab results if the patient mentions them during intake.
5. Confirm that prescriptions documented in the system match what the patient reports taking.

### Current Pain Points (PHP Dashboard)
- **No visual alerting for critical allergies.** Severe allergies look the same as mild ones in the list.
- **Care team shown as a flat list** — unclear who is the attending vs. consulting physician.
- **Lab results require navigation away from the dashboard** — loses patient context.
- **No "last updated" timestamp visible** — difficult to know if the medication list is current.

### Expectations from the Modernization
- **Patient identity is unmistakable.** Name, DOB, and MRN must be prominent and persistent.
- **Allergies are scannable.** She needs to distinguish severe from mild at a glance.
- **Lab results are accessible within the dashboard** — no separate navigation required.
- **Medication list shows last reconciliation date** — if available in the FHIR data.

### Clinical Safety Criticality: HIGH
Nurse Maria is often the last checkpoint before a medication is administered or a procedure is performed. An allergy missed due to poor UI is a direct patient safety risk.

### Feature Parity Standard for This Persona
The new dashboard is acceptable to Nurse Maria if:
- [ ] Patient identity is verified in under 2 seconds of landing on the dashboard.
- [ ] Allergies are displayed with severity clearly indicated.
- [ ] The care team section identifies the responsible physician.
- [ ] She can access lab results without leaving the patient's dashboard context.

---

## 2.3 Dr. Chen — Specialist (Cardiologist)

### Profile
- **Experience:** 18 years in cardiology
- **Technical Comfort:** Low — wants the EHR to "get out of the way"
- **Patient Interaction:** Consults only — sees patients referred by Dr. Sarah and others
- **Time Per Patient Dashboard Interaction:** 2-5 minutes (deep review)

### Goals When Using the Patient Dashboard
1. Review the complete medication list, especially anticoagulants and antiarrhythmics.
2. Examine recent lab results (electrolytes, cardiac markers, coagulation studies).
3. Understand the patient's problem list for cardiac-relevant comorbidities (diabetes, hypertension, CKD).
4. Identify the referring physician in the care team for consult notes.

### Current Pain Points (PHP Dashboard)
- **Medication list is not sortable by class or date.** Must visually scan the entire list.
- **Lab results are in a separate module** — requires context switching and re-identifying the patient.
- **No historical comparison view** — cannot see how lab values have trended over time.
- **Problem list is flat** — cannot distinguish acute vs. chronic conditions at a glance.

### Expectations from the Modernization
- **Sortable medication list.** At minimum, alphabetical. Ideally, by drug class or date prescribed.
- **Lab results integrated into the dashboard.** The Lab Results tab (the additional section chosen) directly addresses this need.
- **Clear problem list with clinical status** — active vs. resolved conditions visually distinct.
- **Care team shows roles clearly** — referring physician, attending, specialists.

### Clinical Safety Criticality: HIGH
Dr. Chen prescribes medications that interact with the patient's existing regimen and comorbidities. A missing medication in the list or an incorrect allergy could lead to a dangerous drug interaction.

### Feature Parity Standard for This Persona
The new dashboard is acceptable to Dr. Chen if:
- [ ] All medications from the PHP dashboard appear in the new Medications and Prescriptions cards.
- [ ] Lab results are available within the dashboard (tabs: Recent, Chemistry, Hematology).
- [ ] The problem list distinguishes active from resolved conditions.
- [ ] Care team roles are identifiable.

---

# 3. Secondary Personas

---

## 3.1 Alex — Medical Assistant

### Goals
- Verify patient demographics before the physician enters.
- Confirm that the medication list in the system matches the patient's reported medications (medication reconciliation preparation).

### Pain Points
- Slow page loads delay rooming workflow.
- No visual cue when a section fails to load — might not notice missing data.

### Modernization Expectations
- The dashboard loads fast enough to not delay rooming (< 2 seconds to interactive).
- Error states are unmistakable — if a section fails, it's obvious something is wrong.

### Clinical Safety Criticality: MEDIUM
Alex does not make clinical decisions, but missing data they don't notice can cascade to the physician.

---

## 3.2 Jordan — Clinic Administrator

### Goals
- Periodically verify that the dashboard is functioning correctly across different workstations.
- Audit readiness: confirm that the system displays complete patient records.
- Evaluate whether the new dashboard is ready for clinic-wide rollout.

### Pain Points
- No visibility into dashboard performance or error rates with the PHP version.
- Rollout decisions are made on anecdotal feedback, not data.

### Modernization Expectations
- The new dashboard should be demonstrably faster and more reliable than the PHP version.
- Observability data (error rates, load times) should be available to support rollout decisions.
- The rollback path (to PHP dashboard) must be documented and testable.

### Clinical Safety Criticality: LOW (operational, not clinical)
Jordan's decisions affect clinic-wide adoption, not individual patient care.

---

## 3.3 Taylor — Front Desk / Reception

### Goals
- Confirm patient identity during check-in by verifying name, DOB, and MRN.
- "Yes, this is the patient who is here for their appointment."

### Pain Points
- None specific to the PHP dashboard — Taylor primarily uses the scheduling module, not the full dashboard.

### Modernization Expectations
- The patient header is visible and prominent if they are directed to the dashboard for identity verification.
- No changes needed — this persona is minimally affected by the dashboard modernization.

### Clinical Safety Criticality: LOW
Taylor's use of the dashboard is limited to the patient header for identity confirmation.

---

## 3.4 Casey — IT / Health Informatics

### Goals
- Deploy and maintain the new dashboard alongside the existing PHP application.
- Troubleshoot API connectivity issues between the frontend and OpenEMR backend.
- Validate that OAuth2 authentication is functioning correctly.
- Support clinicians who have questions about the new interface.
- Verify that no PHI is exposed in logs or client-side storage.

### Pain Points
- The PHP dashboard is difficult to debug — errors are server-side and opaque.
- No structured logging for frontend errors.
- Rollback to a previous version requires server-level changes.

### Modernization Expectations
- Clear, structured error logging (without PHI).
- Environment variable configuration is straightforward.
- The dashboard can be deployed independently of the PHP monolith's release cycle.
- Rollback is fast and documented (one DNS or reverse proxy change).
- The architecture documentation is complete and accurate.

### Technical Safety Criticality: HIGH
Casey is responsible for ensuring the system does not leak PHI and that authentication boundaries are maintained. A misconfiguration that exposes patient data is Casey's responsibility.

---

# 4. Accessibility & Inclusive Design

## 4.1 Users with Visual Impairments

Several personas (particularly in an aging clinical workforce) may have:
- Presbyopia (age-related near-vision decline) — requires sufficient font sizes and contrast.
- Color vision deficiency — cannot rely solely on color to convey clinical severity.
- Screen reader usage (low-vision or blind clinicians accessing the EHR via assistive technology).

### Modernization Requirements
- **WCAG 2.1 AA compliance:** shadcn/ui components meet this standard out of the box.
- **Semantic HTML:** ARIA labels on all clinical cards. The allergy card is an `article` with `aria-label="Allergies"`.
- **Keyboard navigation:** Tab through cards, Enter to expand, Escape to close. Every interactive element is reachable without a mouse.
- **Color is not the sole indicator:** Allergy severity uses both color AND a text label ("Severe" badge next to the red indicator).
- **Font sizes respect system preferences:** Tailwind's `rem`-based spacing scales with browser font size settings.

## 4.2 Users with Motor Impairments

- Some clinicians may use keyboard-only navigation or alternative input devices.
- **Click targets are at least 44x44px** (WCAG AAA target size). shadcn/ui buttons meet this.
- **No hover-dependent functionality** — every hover state has an equivalent focus state for keyboard users.

## 4.3 Users Under Time Pressure (All Clinical Personas)

This is not a disability but a universal constraint. All clinical users are time-pressed.
- **Skeleton loaders** communicate "content loading" faster than spinners.
- **Error states with Retry buttons** are faster than requiring a full page refresh.
- **Persistent patient header** eliminates the need to re-verify patient identity after scrolling.

---

# 5. User Workflow Scenarios

These scenarios serve as both UX validation and the basis for Playwright end-to-end tests.

---

## Scenario 1: Dr. Sarah Opens a Patient Dashboard

**Precondition:** Dr. Sarah is logged in. She has a patient scheduled.

1. Dr. Sarah navigates to the patient dashboard (via search or appointment list).
2. The dashboard shell appears immediately with a skeleton loader in the patient header position.
3. Within 500ms, the patient header loads: "Sarah Johnson | DOB: 03/15/1972 | Female | MRN: MR-0048291 | Active"
4. The clinical cards begin populating in order: Allergies → Problem List → Medications → Prescriptions → Care Team → Lab Results.
5. Each card transitions from skeleton → data (or empty state if no data).
6. Dr. Sarah scans the allergies card. The patient has a severe penicillin allergy — it's marked with a "Severe" badge.
7. She scrolls down. The patient header remains fixed at the top.
8. She clicks the Lab Results tab. The "Recent" tab loads first; she switches to "Chemistry" to review electrolytes.
9. Total time to fully interactive dashboard: < 2 seconds.

**Playwright Test:** `scenario-1-physician-dashboard-load.spec.ts`

---

## Scenario 2: Nurse Maria Verifies Allergies Before Administering Flu Vaccine

**Precondition:** Nurse Maria has the patient's dashboard open.

1. Nurse Maria locates the Allergies card.
2. She scans the list: no egg allergy, no latex allergy, no previous vaccine reaction.
3. The Allergies card shows an empty state for "Vaccine allergies" section: "No known vaccine allergies."
4. She confirms the patient identity in the persistent header.
5. She proceeds with vaccine administration.
6. Total interaction time with the dashboard for this task: < 15 seconds.

**Playwright Test:** `scenario-2-nurse-allergy-check.spec.ts`

---

## Scenario 3: FHIR Endpoint Fails Mid-Session

**Precondition:** Dr. Chen is reviewing a patient's medications.

1. The Medications card loaded successfully on initial page view.
2. Dr. Chen switches to another browser tab to review a clinical guideline.
3. While away, the OpenEMR FHIR server experiences a brief outage.
4. Dr. Chen returns to the dashboard. React Query attempts a background refetch.
5. The refetch fails. The Medications card transitions from data state to error state: "Failed to load medications. Retry?"
6. Dr. Chen clicks "Retry." The FHIR server is back up. The card reloads successfully.
7. All other cards (Allergies, Problem List, etc.) remain in their data state — the failure was isolated to Medications.
8. Dr. Chen was never presented with stale data, and the failure was clearly communicated.

**Playwright Test:** `scenario-3-fhir-outage-recovery.spec.ts`

---

## Scenario 4: Medication Reconciliation by Medical Assistant Alex

**Precondition:** Alex is rooming a patient. He has the dashboard open on a tablet.

1. Alex views the Medications card.
2. He reads each medication aloud to the patient: "Lisinopril 10mg daily, Atorvastatin 20mg nightly, Metformin 500mg twice daily."
3. The patient confirms: "I stopped taking Metformin last month — it was giving me stomach issues."
4. Alex makes a note (in the EHR — out of scope for the dashboard) to flag this for Dr. Sarah.
5. Alex appreciates that the medication list includes dosage and frequency, not just drug names.
6. He also checks the Prescriptions card to confirm what was last prescribed vs. what the patient reports.

**Playwright Test:** `scenario-4-medication-reconciliation.spec.ts`

---

## Scenario 5: Casey Deploys a Dashboard Update

**Precondition:** A new version of the dashboard frontend is ready for staging.

1. Casey pushes to the `staging` branch. Railway automatically builds and deploys.
2. Casey runs the Playwright smoke test suite against the staging URL.
3. All tests pass. The patient header, all five clinical cards, and lab results render correctly.
4. Casey checks the structured logs: no FHIR validation errors, no auth failures.
5. Casey promotes the build to production.
6. Casey verifies that the original PHP dashboard is still accessible at its original URL (untouched).
7. Casey documents the deployment in CHANGELOG.md.

**Playwright Test:** N/A (operational workflow, tested via CI pipeline, not browser automation)

---

# 6. User Expectations: Feature Parity Definition

"Feature parity with the original is the standard" (PRD2_MODERNIZED.md). For each user persona, this means:

| Persona | Feature Parity Means |
| ------- | -------------------- |
| **Dr. Sarah** | All clinical information from the PHP dashboard is present in the same visual organization. No data is missing. Interactions are faster, not different. |
| **Nurse Maria** | Patient identity is unmistakable. Allergies are complete and severity is visible. Lab results are accessible without losing patient context. |
| **Dr. Chen** | Medication list is complete and sortable. Lab results are integrated. Problem list distinguishes active from resolved. Care team roles are clear. |
| **Alex** | Dashboard loads fast. Error states are obvious. Medication details (dosage, frequency) are displayed. |
| **Jordan** | Dashboard is measurably faster and more reliable. Rollback path exists and is documented. Observability data supports rollout decisions. |
| **Taylor** | Patient header is prominent and accurate. No workflow changes required. |
| **Casey** | Deployment is independent of PHP releases. Structured logging is available. Architecture is documented. Rollback is fast and simple. |

---

# 7. What the Modernization Intentionally Does NOT Change

Clarity about what stays the same is as important as what changes:

| Unchanged Element | Reason |
| ----------------- | ------ |
| **Information architecture** | The card layout, section order, and data hierarchy match the PHP dashboard. Users should not need retraining. |
| **Clinical terminology** | The dashboard displays whatever terminology OpenEMR's FHIR API returns. No relabeling or reinterpretation of clinical data. |
| **Data ownership and persistence** | The dashboard is a presentation layer only. All data is owned by OpenEMR. No clinical data is stored in the frontend. |
| **Authorization boundaries** | The dashboard respects all OpenEMR RBAC rules. It displays only what the API returns for the authenticated user. |
| **Workflow integration** | The dashboard does not change how clinicians document, order, or schedule. It only changes how they view patient data. |

---

# 8. User Feedback Channels (Future)

For V1, the modernization project does not include a formal user feedback mechanism. However, the following channels are anticipated for future iterations:

- **Direct observation:** Clinicians using the new dashboard alongside the old one, comparing experiences.
- **Error rate monitoring:** If a specific clinical card has a higher error rate than others, it may indicate a usability issue or a FHIR data quality problem.
- **Load time telemetry:** If the dashboard exceeds the 2-second interactive target, performance investigation is triggered.
- **Clinician interviews:** Structured conversations with each persona type after 1 week of use.

---

# 9. Document Governance

This user analysis is a living document. It is updated when:

- A new user persona is identified (e.g., pharmacist, social worker, patient portal user).
- User feedback from V1 deployment reveals unmet needs or incorrect assumptions.
- The scope of the modernization expands to include additional dashboard sections that serve different user workflows.
- Accessibility requirements change (e.g., new WCAG version, institutional accessibility policy update).

**Last reviewed:** 2026-05-06  
**Next review:** After first clinician feedback session (post-V1 deployment)

---

*This document is part of the Echelon Enterprise Engineering governance suite. It ensures that the OpenEMR Patient Dashboard Modernization is built for real users with real clinical workflows, not for abstract technical requirements. Every architectural decision in ARCHITECTURE_PRD2_MODERNIZED.md can be traced to a user need documented here.*

*Built for clinicians. Validated by clinicians. Accountable to patient safety.*
```

This document is grounded in every discussion we've had about clinical context, feature parity, and the real-world use of the dashboard. Each persona connects directly to architectural decisions (the patient header exists because Taylor and Nurse Maria need identity verification; lab results are tabbed because Dr. Chen needs focused views). The workflow scenarios double as your Playwright test specifications. The feature parity table gives you a clear checklist for the "same but better" standard the PRD demands.