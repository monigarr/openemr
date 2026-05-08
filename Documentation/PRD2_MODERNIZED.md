Additional Requirements: Port the OpenEMR Patient Dashboard to a Modern
Framework

OpenEMR is one of the most widely used open-source electronic health record systems in the
world, built in PHP since 2001 and actively maintained on GitHub today. It works. 
Clinics depend on it. 

Your job is not to redesign it — it is to reimplement it.

The existing patient dashboard is a PHP-rendered, server-side application. 
The UX has already been addressed (May 2025). What has not changed is the underlying technology. 

Your challenge is to port the dashboard to a modern framework of your choosing, consuming
OpenEMR's existing REST and FHIR API as your data layer. 

You are not touching the backend.
You are not redesigning the interface. 

You are moving the presentation layer to a better tool and making the case for why that tool is the right one.

By the end of the week you should have:
● Authentication — Login via OAuth2/OpenID Connect
● Patient header — The persistent identity bar: name, date of birth, sex, MRN, and active
status
● Clinical cards — Allergies, Problem List, Medications, Prescriptions, and Care Team,
each pulling live data from the FHIR API
● One additional section of your choice — Encounter history, lab results, vitals,
immunizations, upcoming appointments, or patient notes are all backed by the existing
API

DELIVERABLE
1. A working reimplementation of the patient dashboard in a modern language and/or framework.
2. Feature parity with the original is the standard. 
3. You must also be able to explain why you chose your framework, what you gained by moving away from PHP, and what tradeoffs came with that choice. 
4. You must document your defense in `Documentation/PATIENT_DASHBOARD_MIGRATION.md` and
keep that file in the repo. That defense is part of the grade.

The framework decision is yours. The UX decision is yours. Own both.

---

## PRD 2 MODERNIZED — Program documentation (canon)

Operational architecture, risks, audit trail, users, and cost/latency for this fork’s **Next.js patient dashboard** track live under **`Documentation/`** (see also **`.cursor/rules/PRD2-Modernized-Scope-and-Docs.mdc`**). In particular:

| Topic | File |
| ----- | ---- |
| Requirements detail | `Documentation/PRD2_MODERNIZED.md` (this file) |
| Architecture | `Documentation/ARCHITECTURE_PRD2_MODERNIZED.md` |
| Observability (Track A + Track B Langfuse) | Same, §11; **ADR-007** in `Documentation/AUDIT_PRD2_MODERNIZED.md` |
| Risks | `Documentation/ARCHITECTURE_RISKS_PRD2_MODERNIZED.md` |
| Framework defense | `PATIENT_DASHBOARD_MIGRATION.md` (repository root; also referenced as `Documentation/PATIENT_DASHBOARD_MIGRATION.md` in some runbooks) |
| Users / scenarios | `Documentation/USERS_PRD2_MODERNIZED.md` |
| Cost / latency | `Documentation/COST_LATENCY_REPORT_PRD2_MODERNIZED.md` |

**Track B optional Langfuse:** `frontend/instrumentation.ts`, `frontend/lib/observability/`, env **`DASHBOARD_LANGFUSE_ENABLE`** + **`LANGFUSE_*`** (+ recommended **`LANGFUSE_ID_SALT`**). Does not change PRD feature requirements; instrumentation is **off** unless explicitly enabled.