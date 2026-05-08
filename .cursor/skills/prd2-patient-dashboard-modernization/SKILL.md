---
name: prd2-patient-dashboard-modernization
description: >-
  Implement or review the OpenEMR Patient Dashboard Next.js app under frontend/.
  Use when adding FHIR cards, auth, proxy routes, Playwright tests, or parity
  work for PRD2 Modernized. Read this skill before large changes to frontend/.
---

# PRD2 Patient Dashboard Modernization

## When to use

- Any substantive work under [`frontend/`](frontend/) (Next.js App Router, next-auth, FHIR client, clinical cards, E2E tests).
- Feature parity, OAuth/FHIR debugging, or documentation updates tied to the modernization track.

## Read order (do not skip for new features)

1. [`Documentation/PRD2_MODERNIZED.md`](Documentation/PRD2_MODERNIZED.md) — requirements and deliverables.
2. [`Documentation/ARCHITECTURE_PRD2_MODERNIZED.md`](Documentation/ARCHITECTURE_PRD2_MODERNIZED.md) — §3 scope, §5 non-functional goals, §7.1 OIDC sequence, §17 failure modes, **§19** directory layout and **§19.2** generation order.
3. [`Documentation/ARCHITECTURE_RISKS_PRD2_MODERNIZED.md`](Documentation/ARCHITECTURE_RISKS_PRD2_MODERNIZED.md) — especially **P0** rows (token exposure **R-001**, CORS/proxy **R-002**) before touching auth or `app/api/fhir/`.
4. [`Documentation/USERS_PRD2_MODERNIZED.md`](Documentation/USERS_PRD2_MODERNIZED.md) — persona expectations, §6 feature parity table, §5 scenarios.
5. [`Documentation/PATIENT_DASHBOARD_MIGRATION.md`](Documentation/PATIENT_DASHBOARD_MIGRATION.md) — when changing stack, deployment, or defending framework choices.

Security rules are also in [`.cursor/rules/PRD2-Modernized-Frontend-Security.mdc`](../../rules/PRD2-Modernized-Frontend-Security.mdc).

## Directory mapping (monorepo)

Architecture §19.1 describes a repo-root Next app; **this fork** keeps the same structure **under** `frontend/`:

| Doc path (conceptual) | This repo |
| --------------------- | --------- |
| `app/` | `frontend/app/` |
| `lib/fhir/`, `lib/auth/` | `frontend/lib/fhir/`, `frontend/lib/auth/` |
| `hooks/` | `frontend/hooks/` |
| `components/` | `frontend/components/` |
| `app/api/fhir/[...resource]/route.ts` | `frontend/app/api/fhir/[...resource]/route.ts` |

## Implementation order (from architecture §19.2)

Respect this dependency order when scaffolding or reviewing PRs:

1. `frontend/lib/fhir/types.ts` (or equivalent) + `frontend/lib/auth/auth.config.ts`
2. `frontend/hooks/use-fhir.ts` (base React Query / fetch wrapper)
3. Resource hooks: `use-patient.ts`, `use-allergies.ts`, `use-problem-list.ts`, `use-medications.ts`, `use-prescriptions.ts`, `use-care-team.ts`, `use-lab-results.ts`
4. `frontend/components/shared/*` — loading skeleton, error fallback, empty state
5. `frontend/components/cards/clinical-card.tsx` (shared wrapper)
6. Individual clinical cards + lab results tabs
7. `frontend/components/layout/patient-banner.tsx`
8. `frontend/components/layout/dashboard-shell.tsx`
9. `frontend/app/` routes, layouts, and API routes (auth callback, FHIR proxy)

## Clinical UI contract

- **Every** clinical card supports **loading**, **empty**, and **error** states (clear copy; retry on error). Users must distinguish “no data” from “not loaded yet” ([`Documentation/USERS_PRD2_MODERNIZED.md`](Documentation/USERS_PRD2_MODERNIZED.md), architecture §17).
- **Patient header** persists while scrolling (wrong-patient prevention).
- **Isolated failure:** one card’s FHIR error must not blank the whole dashboard; other cards keep their last good state where appropriate (see Scenario 3 below).

## Playwright mapping ([`Documentation/USERS_PRD2_MODERNIZED.md`](Documentation/USERS_PRD2_MODERNIZED.md) §5)

Place specs under `frontend/` (e.g. `frontend/e2e/` or `frontend/tests/e2e/`) per project convention — filenames from the doc:

| Scenario | Spec file (target name) |
| -------- | ------------------------ |
| §5 Scenario 1 — Dr. Sarah opens dashboard | `scenario-1-physician-dashboard-load.spec.ts` |
| §5 Scenario 2 — Nurse Maria allergy check | `scenario-2-nurse-allergy-check.spec.ts` |
| §5 Scenario 3 — FHIR outage mid-session | `scenario-3-fhir-outage-recovery.spec.ts` |
| §5 Scenario 4 — Medication reconciliation (Alex) | `scenario-4-medication-reconciliation.spec.ts` |
| §5 Scenario 5 — Deployment (Casey) | Operational / CI — not a single browser spec |

## Audit and ADRs

Material decisions (auth changes, new trust boundaries, proxy semantics, new clinical sections) belong in [`Documentation/AUDIT_PRD2_MODERNIZED.md`](Documentation/AUDIT_PRD2_MODERNIZED.md) per that document’s audit model: if it is not recorded, treat it as undocumented for compliance and handoff.

## Related Cursor rules

- [`.cursor/rules/PRD2-Modernized-Scope-and-Docs.mdc`](../../rules/PRD2-Modernized-Scope-and-Docs.mdc) — branch, doc canon, PHP monolith boundary.
- [`.cursor/rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc`](../../rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc) and [agent-team-prd2 skill](../agent-team-prd2/SKILL.md) — subagent roles for PRD2 Modernized (and cross-track pointers).
