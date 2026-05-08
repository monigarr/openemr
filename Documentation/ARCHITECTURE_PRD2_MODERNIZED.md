# ============================================================================
# PROJECT ARCHITECTURE
# ============================================================================
# Project Name:
#   OpenEMR Patient Dashboard Modernization (Presentation Layer)
#
# Repository:
#   https://github.com/monigarr/openemr/tree/prd2_af_modernized
#
# Upstream Source of Truth:
#   https://github.com/openemr/openemr (NEVER push to this repo)
#
# Version:
#   0.1.1
#
# Status:
#   Planning & Initial Development
#
# Classification:
#   X3–X4 (Cross-system frontend/backend integration with Institutional impact)
#
# Authors:
#   Monica Peters, AI Engineering Agents (Cursor)
#
# Organization:
#   MoniGarr / M.O.M. Operating Model 
#         extended with Gauntlet AI Methodologies
#
# Primary Maintainers:
#   Monica Peters
#
# Created:
#   2026-05-06
#
# Last Updated:
#   2026-05-08 (Track B Langfuse §11/§19; privacy §10; monitoring goals; directory tree)
#
# License:
#   MIT
# ============================================================================
#
# DESCRIPTION
# ----------------------------------------------------------------------------
# High-level architectural definition for the OpenEMR Patient Dashboard
# presentation layer modernization using:
#
# - MoniGarr Operating Model (M.O.M.)
# - M.I.L.E. (MoniGarr Intelligence-Led Engineering)
# - Echelon Enterprise Engineering Protocols
#
# This document defines:
# - Architectural intent for a hybrid brownfield, frontend-only migration
# - Constraints required to preserve the legacy OpenEMR PHP monolith
# - Trust boundaries for OAuth2/OpenID Connect
# - AI-native strategies for rapid development with Cursor
# - Security posture for healthcare data (HIPAA awareness)
# - Operational expectations for clinical environments
# - Governance requirements for an additive, reversible deployment
# - Scalability assumptions for single-page application delivery
# - Human accountability structures for all AI-generated artifacts
#
# ============================================================================
```

---

# 1. Executive Summary

## Overview

> This system is an AI-Native, brownfield-safe, enterprise-grade presentation layer designed to reimplement the OpenEMR Patient Dashboard using a modern JavaScript framework. It consumes the existing, stable PHP backend exclusively through REST and FHIR APIs, ensuring operational continuity, strict patient data ownership, and complete reversibility.

## Business Objective

* **Primary Business Problem:** The OpenEMR Patient Dashboard’s PHP server-side rendering limits developer velocity, inhibits modern interactive UX patterns, and couples UI delivery to the monolith’s release cycle.
* **Expected ROI:** Decoupled frontend development velocity; reduced page-reload friction for clinical users; instant, AI-assisted feature iteration without destabilizing the backend.
* **Strategic Value:** Proves that OpenEMR can adopt a modern, maintainable component architecture, opening a path for future incremental modernization across the entire EHR codebase.
* **Long-Term Operational Intent:** This layer remains an additive, removable shell. If maintained, it becomes the standard patient-facing frontend; if abandoned, the original PHP dashboard remains untouched and fully functional.

## Operational Philosophy

This project follows:

* **AI-First Engineering** – Cursor and AI agents accelerate scaffolding, documentation, testing, and review.
* **AI-Native Architecture** – The frontend is built with React Server Components and intelligent client-side state management, optimized for AI-assisted maintenance.
* **Human-in-the-Loop Accountability** – All OAuth2 flows, API proxy rules, and deployments are validated by a human operator.
* **Sovereign Engineering Principles** – The modernization respects the upstream project’s ownership, community governance, and patient safety requirements.
* **Echelon Enterprise Operational Standards** – Code headers, documentation, and observability are non-negotiable.
* **Scale-Adaptive Rigor** – The architecture supports a single developer today and a multi-team enterprise tomorrow.

---

# 2. MoniGarr Operating Model (M.O.M.)

## Core Engineering Principles

### 2.1 Human Accountability First

AI accelerates execution but does not replace:

* architectural decision-making on the Strangler-Fig pattern
* OAuth2 security validation
* final merge approval into the modernization branch
* clinical UX sign-off (feature parity with the original dashboard)

### 2.2 Ancient + Human + Artificial Intelligence Integration

This system integrates:

* **Traditional intelligence:** The deeply tested, decade-old PHP business logic remains the unmodified source of truth.
* **Human contextual reasoning:** A developer interprets OpenEMR’s complex FHIR resource relationships and ensures clinical cards match the original intent.
* **Artificial intelligence acceleration:** Cursor generates component templates, TypeScript types for FHIR resources, and comprehensive test suites.

All three layers must remain visible and auditable in the commit history.

### 2.3 Enterprise from Day One

All systems must support:

* **Security:** Encrypted tokens, HTTP-only cookies, CSP headers.
* **Maintainability:** Modular shadcn/ui components, clear separation of data hooks (React Query) and presentation.
* **Observability:** Structured logging of API failures, rendering errors, and auth failures.
* **Extensibility:** Additional FHIR sections (lab results, encounters) can be added as new cards without modifying the core dashboard layout.
* **Documentation:** This document, PATIENT_DASHBOARD_MIGRATION.md, and a live README.md.
* **Rapid Handoff:** Any Next.js developer can clone and run this project.
* **Operational Continuity:** The original PHP dashboard continues to serve if the new frontend is taken offline.

No prototype-grade architecture is permitted in this repository.

### 2.4 Documentation as Infrastructure

Documentation is treated as:

* operational infrastructure (explaining how to run the frontend alongside a live OpenEMR instance)
* onboarding infrastructure (for new developers or clinical IT staff)
* governance infrastructure (proving HIPAA awareness and data isolation)
* legal protection infrastructure (clearly defining that the backend is unchanged)
* continuity infrastructure (ensuring the project outlives any single contributor)

### 2.5 Handoff-Ready Engineering

Systems must be transferable to:

* engineering teams at a healthcare institution
* auditors evaluating the frontend’s security posture
* compliance officers verifying that PHI does not leak into client-side analytics
* OpenEMR community maintainers evaluating the modernization branch
* external vendors within < 1 day of onboarding.

---

# 3. System Scope

## In Scope

* **Frontend Modernization:** Reimplementation of the patient dashboard UI using Next.js 14+ (App Router).
* **Authentication:** Full OAuth2/OpenID Connect login flow using `next-auth` pointed at OpenEMR’s OIDC discovery endpoint.
* **Data Consumption:** Strictly typed `fetch` wrappers for OpenEMR’s REST and FHIR APIs.
* **Clinical Cards:** Allergies, Problem List (Conditions), Medications, Prescriptions (MedicationRequests), and Care Team.
* **Additional Section:** Lab Results (Observation resource with category=laboratory).
* **Responsive Rendering:** Tailwind CSS and shadcn/ui components matching the May 2025 redesign layout.
* **Reverse Proxy (if needed):** Next.js API routes to proxy FHIR requests if CORS restricts direct browser access.
* **Deployment:** Railway.com or Vercel.

## Out of Scope

* **Backend Replacement:** Absolutely no modification of PHP business logic, database schema, or authentication server.
* **Master Branch Core Changes:** The upstream `https://github.com/openemr/openemr` must never receive unstable changes from this project.
* **Redesign:** The UX is fixed. The project achieves feature parity with the existing interface.
* **Direct Database Access:** No SQL or schema manipulation is permitted.
* **Full EHR Modernization:** The calendar, billing, and admin panels are explicitly not part of this project.

---

# 4. STRATA-X Scale Classification

| Level | Description                      |
| ----- | -------------------------------- |
| X0    | Micro modifications              |
| X1    | Local feature                    |
| X2    | Component architecture           |
| X3    | Cross-system architecture        |
| X4    | Institutional systems            |
| X5    | Sovereign / generational systems |

## Current Classification: X3 - X4

**Rationale:**

* **X3 (Cross-system architecture):** The frontend must integrate securely with a separate, immutable backend over REST/FHIR, crossing not just a network boundary but an OAuth2 trust boundary.
* **X4 (Institutional systems):** The dashboard is used in real clinical workflows. A failure to load the Problem List or an incorrect allergy display could impact patient care. The system must meet institutional reliability standards even in its initial release.

---

# 5. Architecture Goals

## Functional Goals

* **Goal 1:** Log in via OpenID Connect and obtain an access token.
* **Goal 2:** Display a persistent patient header (Name, DOB, Sex, MRN, Active Status) fetched from a FHIR `Patient` resource.
* **Goal 3:** Render five clinical cards (Allergies, Problem List, Medications, Prescriptions, Care Team) with live FHIR data.
* **Goal 4:** Render one additional complex section (Lab Results) with filtering/tabs.
* **Goal 5:** Handle Loading, Error, and Empty states for every component gracefully.

## Non-Functional Goals

### Security
* OAuth2 token never exposed to browser JavaScript (HTTP-only session cookie).
* All external API calls encrypted (TLS).
* Content Security Policy (CSP) headers configured.
* No PHI logged to external telemetry services.

### Privacy
* Patient data is rendered transiently; no client-side persistence (localStorage/IndexedDB) for clinical data.
* Server-side session store holds only the access token, not the patient PHI.

### Stability
* If the OpenEMR API returns a 5xx error, the frontend must degrade gracefully with a retry option.
* No unhandled promise rejections in any clinical card.

### Reliability
* All FHIR queries are typed with `zod` or a similar runtime validation library to catch schema drift.
* React Query automatically retries failed requests with exponential backoff.

### Performance
* Skeleton loaders provide immediate visual feedback (< 100ms to LCP).
* Next.js code splitting ensures the lab results table code is lazy-loaded.
* Cached FHIR data (stale-while-revalidate, 30-second TTL) minimizes redundant API calls.

### Accessibility
* shadcn/ui components meet WCAG 2.1 AA standards out of the box.
* Semantic HTML and ARIA labels used for all clinical cards.
* Keyboard navigation supported for tabbing between sections.

### Observability
* Structured console logging for API response times and error rates (no PHI).
* OpenTelemetry-ready labels on all `fetch` spans (future integration).

### Maintainability
* Modular component structure: changing the Allergy card does not touch the Medication card.
* The `lib/fhir` directory isolates all API shape knowledge.

### Scalability
* Serverless deployment model (Vercel/Railway) scales horizontally with demand.
* The Next.js API proxy is stateless, requiring only a shared session store (if needed).

### Portability
* The frontend requires only the `OPENEMR_BASE_URL` and OAuth2 credentials; it can point at any OpenEMR instance.

### Disaster Recovery
* Full rollback available by changing the DNS record to the original PHP dashboard.
* Feature flags can disable individual clinical cards if a backend endpoint is unavailable.

---

# 6. High-Level System Architecture

## Architectural Style

**Hybrid Brownfield AI-Native Frontend (Strangler-Fig Pattern)**

The modernization layer wraps around the legacy system, replacing the patient dashboard UI while the PHP monolith continues to own all business logic.

## System Diagram

```text
[ Browser ]
    ↓ (HTTPS, HTTP-Only Session Cookie)
[ Next.js Frontend (Vercel / Railway) ]
    ├── Server Components (App Router)
    └── Client Components (React + React Query)
    ↓ (OAuth2 Bearer Token, fetched server-side)
[ OpenEMR PHP Monolith (Existing) ]
    ├── /oauth2/default/token
    ├── /apis/default/fhir/Patient
    ├── /apis/default/fhir/AllergyIntolerance
    ├── /apis/default/fhir/Condition
    ├── /apis/default/fhir/MedicationRequest
    └── /apis/default/fhir/Observation
```

**Data Flow:**
1. User visits `/login`. Next.js redirects to OpenEMR’s OIDC provider.
2. Successful auth returns tokens. `next-auth` stores the access token encrypted in the HTTP-only session.
3. A React Server Component or an API Route reads the token server-side and fetches FHIR resources from the OpenEMR API.
4. Data is passed as props to Client Components (clinical cards), which handle interactivity (sorting, filtering, tab switching) using `useState` and React Query for background refetches.

---

# 7. AI-Native Engineering Model

## Canonical agent roster (source of truth)

**Human Lead (both tracks):** Monica Peters — architecture approval, security sign-off, deployment authorization, final sign-off on all documents. **Not an AI agent.**

Role names, model defaults, responsibilities, and governance are defined in [`.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc`](../.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc). The sections below use that terminology only.

**Track A — Clinical CoPilot** applies to `interface/modules/custom_modules/oe-module-clinical-copilot/`. **Track B — PRD2 Modernized** applies to this Next.js patient dashboard (`frontend/`) and related governance documentation. This architecture is Track B–centric; Track A is included so the program shares one vocabulary.

## AI-First Philosophy

AI participates in:

* **Planning:** Cursor analyzes the PRD and generates the initial file structure.
* **Analysis:** AI reviews OpenEMR’s FHIR swagger schemas and proposes TypeScript interfaces.
* **Architecture:** AI drafts this ARCHITECTURE.md based on the M.O.M. template.
* **Implementation:** Cursor generates entire React components from comments like `// FHIR AllergyIntolerance card with loading skeleton`.
* **Review:** AI performs a preliminary code review, checking for missing error states.
* **Documentation:** AI generates the PATIENT_DASHBOARD_MIGRATION.md defense document.
* **Testing:** AI scaffolds unit tests for the `useFhir` hook and integration tests for each clinical card.

## AI-Native Capabilities

* **Roles (Track B — this dashboard):** **Planner Agent** (planning and task assignment), **Documentation Agent** (repo docs and architecture alignment), **Security Audit Agent** (review-only security gate; token and proxy posture), **auth-implementer** (OAuth2/OIDC in `lib/auth/`, `app/api/auth/`, `app/login/`), **fhir-client-builder** (`lib/fhir/`, `hooks/`), **card-component-builder** and **layout-shell-builder** (clinical cards and dashboard shell), **deployment-verifier** (deployment config, Playwright, integration verification; no application feature code), **Verification Agent** (regression, schema/runtime checks, E2E), **Performance Agent** (rendering, bundles, caching).
* **Roles (Track A — Clinical CoPilot):** **Lead Agent**, **Planner Agent**, **Subagent 1 — File Processor**, **Subagent 2 — Summarizer**, **Subagent 3 — EHR Writer** (see roster table in §8).
* **Workflows:** A single prompt in Cursor generates a fully typed FHIR API client, a React Query hook, the card component, and its stories/tests.
* **Orchestration:** The developer orchestrates role outputs, selecting the best generation and refining it.
* **Retrieval Systems:** The FHIR JSON schemas are embedded in the project for Cursor to reference, ensuring hallucinations-resistant code generation.
* **Evaluation Pipelines:** Playwright tests generated by AI verify that every card transitions through Loading → Data/Empty/Error states correctly.

## Human-in-the-Loop Controls

Humans retain authority over:

* **Deployment:** Manual promotion to Railway/Vercel production.
* **Security Decisions:** Verification that the `next-auth` callback correctly validates the token `iss` and `aud` claims.
* **Architectural Approval:** Final sign-off on this ARCHITECTURE.md.
* **Compliance:** Ensuring no PHI is accidentally logged to the browser console.
* **Data Governance:** Confirming that the proxy layer does not cache clinical data.
* **Final Validation:** Comparing the new dashboard side-by-side with the original PHP dashboard to confirm feature parity.

---

## 7.1 OAuth2 / OpenID Connect Flow (auth-implementer)

The **auth-implementer** role implements this exact sequence:

1. **User visits `/login`**
   - Next.js `app/login/page.tsx` triggers `signIn("openemr")` from next-auth.
   - User is redirected to OpenEMR's `/oauth2/default/authorize`.

2. **OpenEMR authenticates user**
   - Standard OpenID Connect Authorization Code Flow with PKCE.
   - User consents to scopes: `openid profile fhirUser offline_access`.

3. **OpenEMR redirects to `/api/auth/callback/openemr`**
   - next-auth exchanges the authorization code for tokens at OpenEMR's `/oauth2/default/token`.
   - The `jwt` callback in `auth.config.ts` stores: `access_token`, `refresh_token`, `expires_at`, `patient` (FHIR resource URL).

4. **Session created**
   - next-auth encrypts the token into an HTTP-only session cookie.
   - User redirected to `/dashboard`.

5. **Dashboard requests FHIR data**
   - Server component or API route reads the `access_token` from the session.
   - `lib/fhir/client.ts` attaches `Authorization: Bearer ${token}` to all FHIR requests.
   - If the access token is expired, the `jwt` callback in next-auth automatically refreshes it using the `refresh_token` before the FHIR request fires.

6. **Token refresh (transparent)**
   - next-auth's JWT callback checks `expires_at` before every session read.
   - If expired, calls OpenEMR's `/oauth2/default/token` with `grant_type=refresh_token`.
   - Updates the encrypted JWT with the new access token.
   - The user never sees a token or a re-login prompt.

**Key next-auth configuration (implement in `lib/auth/auth.config.ts` — auth-implementer):**

```typescript
// Pseudocode for auth-implementer — implement exact API
export const authConfig = {
  providers: [
    {
      id: "openemr",
      name: "OpenEMR",
      type: "oidc",
      issuer: process.env.OPENEMR_OAUTH2_ISSUER,
      clientId: process.env.AUTH_OPENEMR_ID,
      clientSecret: process.env.AUTH_OPENEMR_SECRET,
      authorization: { params: { scope: "openid profile fhirUser offline_access" } },
    },
  ],
  callbacks: {
    async jwt({ token, account }) {
      if (account) {
        token.accessToken = account.access_token;
        token.refreshToken = account.refresh_token;
        token.expiresAt = account.expires_at;
      }
      if (Date.now() < token.expiresAt * 1000) return token;
      // Refresh logic here
      return refreshedToken;
    },
    async session({ session, token }) {
      session.accessToken = token.accessToken;
      return session;
    },
  },
};
```

---

# 8. Agent Council Review (ACR)

**ACR** aligns with the roster: the **Planner Agent** confirms ACR checklist item completion in `Documentation/AUDIT_PRD2_MODERNIZED.md` before marking a task complete.

## Track A — Clinical CoPilot (PHP module)

Use with `interface/modules/custom_modules/oe-module-clinical-copilot/**` — full non-negotiables and flow live in [`.cursor/rules/PRD-2-AgentForge-Agent-Roster.mdc`](PRD-2-AgentForge-Agent-Roster.mdc).

* **Orchestration:** OpenAI **tool-loop** in the PHP module; **LangChain and LangGraph are out of program scope** (no LangChain/LangGraph orchestration or graph runtime). See **ADR-007** in [`Documentation/AUDIT_PRD2_MODERNIZED.md`](AUDIT_PRD2_MODERNIZED.md).
* **Observability:** **Langfuse** is **in program scope** for Track A as the optional, admin-gated export path for copilot traces when configured (`LANGFUSE_*` and module globals). **Track B** uses the **same Langfuse product** for optional FHIR-proxy spans when `DASHBOARD_LANGFUSE_ENABLE` and `LANGFUSE_*` are set on the Next.js server (metadata-first; see §11). The integration is **not** superseded by a different vendor. Same ADR reference.

**Human Lead (both tracks):** Monica Peters — Architecture approval, security sign-off, 
deployment authorization, final sign-off on all documents. NOT an AI agent.

| Role | Default model | Why (summary) |
| ---- | ------------- | ------------- |
| **Lead Agent** | **GPT-4o** | Planning, decomposition, complex system prompts. |
| **Planner Agent** | **GPT-4o-mini** | Routing, lower latency/cost, strong instruction following. |
| **Subagent 1 — File Processor** | **Gemini 1.5 Flash** | Large context for patient PDFs / messy OCR. |
| **Subagent 2 — Summarizer** | **Llama 3.1 70B** | Structured JSON; concise clinical summaries. |
| **Subagent 3 — EHR Writer** | **GPT-4o-mini** | OpenEMR-shaped writes via **server-controlled** APIs/hooks only (no direct DB). |

## Track B — PRD2 Modernized (Next.js + governance docs)

These roles extend the team for dashboard modernization and repo documentation. Where the 
source file omits a model, default to **GPT-4o-mini** for narrow tasks and **GPT-4o** or 
**Claude Sonnet** for architecture/security reviews.

| Role                    | Suggested model                        | Responsibilities                         |
| ----------------------- | -------------------------------------- | ---------------------------------------- |
| **Planner Agent**       | **claude-sonnet-4-20250514 or gpt-4o** | Planning, decomposition, task assignment. Does NOT spawn Workers until human Lead Architect has signed off on ADR-001 through ADR-007 in Documentation/AUDIT_PRD2_MODERNIZED.md. |
| **auth-implementer**    | **claude-sonnet-4-20250514**           | OAuth2/OIDC flow only. lib/auth/, app/api/auth/, app/login/. Does NOT touch FHIR, components, or deployment. |
| **fhir-client-builder** | **claude-sonnet-4-20250514**           | FHIR types, client, and hooks only. lib/fhir/, hooks/. Does NOT build UI or touch auth. |
| **card-component-builder** | **claude-sonnet-4-20250514**        | All clinical card UI components. components/cards/, components/shared/. Consumes hooks from fhir-client-builder. Enforces three-state pattern (Loading/Error/Empty) on all cards per ADR-005. |
| **layout-shell-builder**   | **claude-sonnet-4-20250514**        | Dashboard shell, patient banner, page routing. components/layout/, app/dashboard/. Consumes cards as children. |
| **deployment-verifier**    | **claude-sonnet-4-20250514**        | Deployment config, Playwright tests, final integration verification. Does NOT write application code. |
| **Security Audit Agent** | **claude-sonnet-4-20250514**           | Review-only gate, not a code author. Reviews auth-implementer output for token exposure (Risk R-001). Reviews proxy route for SSRF. Verifies no PHI in client code. |
| **Performance Agent**   | **gpt-4o-mini**                        | Rendering, bundles, caching; align with architecture latency goals. |
| **Documentation Agent** | **gpt-4o-mini**                        | Repo docs, onboarding, architecture alignment (respect fork doc policy). |
| **Verification Agent**  | **gpt-4o-mini** (+ CI/tests)           | Regression mindset, schema/runtime validation, E2E scenarios. |

## Agent governance (non-negotiable)

- No autonomous deployment.
- No unsupervised merge approval.
- Human accountability mandatory.
- All outputs auditable.
- Rollback capability preserved.
- Planner Agent must confirm ACR checklist item completion in `Documentation/AUDIT_PRD2_MODERNIZED.md` before marking a task complete.

## Project-specific execution rules

* No autonomous production deployment. The `railway up` command is executed by a human.
* No self-authorizing behavior. The **Security Audit Agent** flags proxy and token-boundary decisions; a human implements and approves them.
* All outputs require **Verification Agent** (and human) review: Echelon headers, invalid FHIR/JSON edge cases, and visual parity against the original PHP screen.
* Human override always available. The final decision on the framework (Next.js) was a human architectural choice, validated by **Planner Agent** / **Documentation Agent** analysis of community support and FHIR ecosystem compatibility.

---

# 9. Security Architecture

## Security Philosophy

Security is:
* **proactive:** All dependencies are scanned with `npm audit`.
* **layered:** Token stored in session; proxy validates FHIR resource types; frontend sanitizes rendered text.
* **observable:** Auth failures are logged with correlation IDs (no tokens).
* **continuously validated:** The OIDC flow is tested against OpenEMR’s demo server before every release.

## Security Requirements

* **Authentication:** OpenID Connect Authorization Code Flow with PKCE.
* **Authorization:** Frontend only displays what the API returns; route protection via Next.js middleware checking session presence.
* **RBAC:** OpenEMR’s RBAC is fully respected—the frontend cannot elevate privileges.
* **Audit Logging:** The PHP backend still produces its standard audit log.
* **Encryption:** All client-to-Vercel and Vercel-to-OpenEMR traffic uses TLS 1.3.
* **Secrets Management:** OAuth2 client secret and `next-auth` secret are stored as environment variables on Railway/Vercel; never committed.
* **Dependency Scanning:** `npm audit` runs in CI; Dependabot enabled.
* **Supply Chain Protection:** Lockfile committed; `pnpm` used for deterministic installs.
* **AI Prompt Injection Mitigation:** This architecture injects no user-generated clinical notes into an LLM prompt. There is no LLM endpoint to attack. The frontend is purely a data presentation layer.
* **Data Isolation:** Each user’s session is cryptographically isolated; the frontend has no mechanism to request another user’s session data.

## Threat Model

* **Internal Threats:** A developer accidentally logs a FHIR resource to the console. Mitigated by a strict ESLint rule (`no-console.error` only). Reviewed in code review.
* **External Threats:** XSS attack attempting to deface a clinical card. Mitigated by React’s default escaping and a strict Content Security Policy.
* **AI Misuse Risks:** Cursor generates a component that writes PHI to `localStorage`. Mitigated by the Verification Agent review step and the hard rule: no client-side persistence of clinical data.
* **Operational Threats:** The OpenEMR backend goes down. Mitigated by the frontend’s error state UI displaying “Patient data currently unavailable. Please check back.”
* **Social Engineering Risks:** N/A—the frontend does not handle credential entry; it redirects to the trusted OpenEMR login screen.

---

# 10. Privacy & Data Governance

## Data Classification

| Classification | Description                                      | Example in this project                     |
| -------------- | ------------------------------------------------ | ------------------------------------------- |
| Public         | Safe for public release                          | The MIT license, this ARCHITECTURE.md        |
| Internal       | Restricted operational data                      | Vercel deployment logs (non-PHI)             |
| Confidential   | Sensitive business data                          | OAuth2 client secrets                        |
| Sovereign      | Protected cultural/community data                | Patient PHI (Name, DOB, MRN, Allergies)      |

## Sovereign AI Considerations

* **Indigenous Data Governance:** While this project is a generic technical modernization, OpenEMR is used globally, including in Indigenous-serving clinics. Therefore, the frontend inherits the backend’s data governance policies. **Clinical PHI** is not written to client storage, is not sent to external AI summarizers (see below), and is not included in Track B Langfuse exports: optional Langfuse spans are **metadata-only** (resource type, operation, HTTP status) plus **SHA-256–hashed** `userId` / `sessionId` when **`DASHBOARD_LANGFUSE_ENABLE`** is on (§11). Enabling Langfuse remains an **operator choice** subject to BAAs and region policy. FHIR payloads move only between the browser, the Next.js server, and the trusted OpenEMR API as described in §5–§7.
* **Language Preservation Protections:** No patient notes or text fields are piped to external AI summarization services without a future explicit governance gate (out of scope).
* **Cultural Safety Considerations:** The UI must accurately display the patient’s preferred name and demographic fields as configured in OpenEMR without bias or modification.

---

# 11. Observability Architecture

## Observability Stack

* **Langfuse (in program scope — Track A and Track B):** **Track A:** optional observability export for the PHP copilot module when enabled via `LANGFUSE_*` and OpenEMR globals. **Track B:** optional observability via the Langfuse JS SDK (`@langfuse/tracing` + `@langfuse/otel`) registered from Next.js `instrumentation.ts`, gated by **`DASHBOARD_LANGFUSE_ENABLE`** and the same `LANGFUSE_PUBLIC_KEY` / `LANGFUSE_SECRET_KEY` (and optional `LANGFUSE_BASE_URL`). The FHIR proxy route (`app/api/fhir/[...path]/route.ts`) records span metadata only: **resource type**, **operation**, **HTTP status** — not logical ids, search parameters, or payloads. **Trace `userId` and `sessionId`** are **SHA-256 hex digests** using optional **`LANGFUSE_ID_SALT`** (same variable as Track A): `sha256(sub \0 salt)` and `sha256(sub \0 langfuseSessionSeed \0 salt)` where `sub` is the OIDC subject from the Auth.js JWT and `langfuseSessionSeed` is a random UUID stored in the JWT at sign-in and kept across refresh. This is the **documented in-program observability integration** for both tracks; it does **not** depend on LangChain or LangGraph (see **ADR-007** in [`Documentation/AUDIT_PRD2_MODERNIZED.md`](AUDIT_PRD2_MODERNIZED.md)).
* **Other OpenTelemetry (optional, Track B):** Additional OTEL instrumentations may be added later; they remain **complementary** to Langfuse export, not a replacement for the program’s Langfuse choice.
* **Structured Logging:** The proxy layer logs `GET /fhir/AllergyIntolerance?patient=123` with response times and status codes. Patient IDs and all other PHI query params are hashed in logs.
* **Error Tracking:** A client-side `ErrorBoundary` component wrapping each clinical card catches rendering failures and reports a non-PHI stack trace.

## Monitoring Goals

* **Langfuse (optional):** When **Track B** export is on, use the Langfuse UI to filter **`fhir-proxy-get`** and review latency or error rates by **resource type** (no PHI in attributes). **Track A** copilot traces remain separate by name and tags.
* **System Reliability:** Track the ratio of successful FHIR resource fetches to 500 errors.
* **AI Behavior Tracking:** If AI-generated code is deployed, track bug reports specific to auto-generated components.
* **Anomaly Detection:** Alert if the `/api/auth/signin` endpoint starts returning 401s en masse.
* **Regression Visibility:** Playwright end-to-end tests run on each commit to confirm all five clinical cards render without crashing.
* **Operational Transparency:** A public status page (optional) could show the health of the frontend and the reachability of the OpenEMR backend.

---

# 12. Verification & Evaluation

## Verification Philosophy

All AI outputs are:
* **untrusted by default:** The component generated by Cursor is not assumed to be clinically safe.
* **verified before action:** The developer runs `pnpm dev`, navigates to a real patient, and visually confirms allergy names match the PHP version.
* **traceable:** The AI interaction and the human’s subsequent edits are in the git history.
* **reproducible:** Any developer can regenerate the component with the same prompt.

## Evaluation Categories

* **Functional Correctness:** Does the Allergy card list exactly the same allergies as the original PHP dashboard for Patient ID 123?
* **Hallucination Resistance:** Cursor does not invent FHIR resource fields. The embedded FHIR schema files ground the AI.
* **Security Compliance:** The `next-auth` callback strictly verifies the `iss` claim matches the configured OpenEMR URL.
* **Adversarial Testing:** What does the UI render if the FHIR API returns a Patient resource with an empty `name` array? (Graceful fallback: “Name not available”).
* **Edge-Case Handling:** The Patient `birthDate` is missing. The `PatientBanner` shows a muted `—` and not a broken empty `<span>`.
* **Regression Testing:** A Playwright script loads the dashboard and asserts the presence of exactly five clinical card headings.

---

# 13. Repository Governance

## Required Repository Standards

Every repository must include:

* `README.md` – Setup instructions, link to this architecture doc, link to original PHP dashboard.
* `Documentation/ARCHITECTURE_PRD2_MODERNIZED.md` – This document (PRD2 modernization governance markdown lives alongside other OpenEMR `Documentation/` assets).
* `Documentation/AUDIT_PRD2_MODERNIZED.md` – AI and Human audit trail.
* `Documentation/USERS_PRD2_MODERNIZED.md` – User personas and how they interact with the dashboard.
* `VERIFY.md` – Steps to verify feature parity against the original dashboard.
* `SECURITY.md` – Security policy and vulnerability reporting procedure.
* `CHANGELOG.md`
* `CONTRIBUTING.md` – Explains the brownfield rules: never modify PHP.
* `LICENSE` – MIT.
* `docs/` – Contains FHIR resource examples.
* `internal/` – Contains roadmap and risk analysis.

---

# 14. Echelon Engineering File Standards

## Mandatory File Header Requirements

Every production code file must contain:

* file purpose
* author
* creation date
* update date
* usage examples
* dependencies
* security notes
* performance notes
* license
* operational considerations

## Example Header (TypeScript)

```typescript
/*
 * ============================================================================
 * FILE: components/cards/lab-results.tsx
 * AUTHOR: Monica Peters, Cursor AI Agent
 * CREATED: 2026-05-06
 * LICENSE: MIT
 *
 * PURPOSE:
 * Renders the Lab Results section using FHIR Observation resources
 * filtered by category=laboratory. Includes tabs for Recent, Chemistry,
 * Hematology.
 *
 * USAGE:
 *   <LabResultsTab patientId="123" />
 *
 * DEPENDENCIES:
 *   - @tanstack/react-query
 *   - @/hooks/use-fhir
 *   - shadcn/ui (Tabs, Table, Skeleton)
 *   - zod (runtime FHIR validation)
 *
 * SECURITY:
 *   - Patient ID is passed securely from the Next.js server session.
 *   - No PHI is logged to the client console.
 *
 * PERFORMANCE:
 *   - Lazy-loaded via next/dynamic.
 *   - Data cached with stale-while-revalidate (30s TTL).
 *
 * OPERATIONAL CONSIDERATIONS:
 *   - If the FHIR server is unreachable, a retry button is displayed.
 *
 * ============================================================================
 */
```

---

# 15. Deployment Architecture

## Environments

| Environment | Purpose                              | Railway/Vercel Branch |
| ----------- | ------------------------------------ | --------------------- |
| Local       | Development (`pnpm dev`)             | `prd2_af_modernized`  |
| Dev         | Shared testing with client           | `dev`                 |
| Staging     | Pre-production validation            | `staging`             |
| Production  | Live clinical use (future)           | `main`                |

## CI/CD Philosophy

* **Automated validation:** `pnpm lint`, `pnpm build`, Playwright smoke tests run on every push to `prd2_af_modernized`.
* **Security scanning:** `npm audit` and Snyk scanning in CI.
* **Reproducible builds:** `pnpm-lock.yaml` ensures identical dependency trees.
* **Rollback support:** Railway/Vercel instant rollback via dashboard.
* **Artifact traceability:** Git SHA tagged on every deployment.

---

# 16. Scalability Strategy

* **Concurrency Assumptions:** Designed for a single clinic’s staff (< 50 concurrent users viewing patient dashboards). No global CDN caching of clinical data.
* **Scaling Model:** Stateless Next.js server. If deployed on Vercel, horizontal scaling is automatic. On Railway, replicas can be increased.
* **Infrastructure Limits:** The bottleneck is the existing OpenEMR PHP server. The frontend adds minimal overhead (a simple Node.js proxy).
* **AI Inference Scaling:** Not applicable. This release contains no LLM inference.
* **Caching Strategy:** React Query caches FHIR data client-side for one user session; server-side caching of clinical data is explicitly disabled to avoid serving stale PHI.
* **Database Scaling:** No database is added by this modernization layer.

---

# 17. Failure Modes & Recovery

## Failure Expectations

Assume:
* OpenEMR’s FHIR API returns a 500 error.
* The OIDC token refresh fails.
* A newly added OpenEMR module returns an unexpected FHIR extension.
* Railway restarts the server mid-session.

## Recovery Strategies

* **Fallback Behavior:** Every clinical card renders an error state with a “Retry” button. The card shows the last successfully fetched data (stale cache) if available.
* **Graceful Degradation:** If the Lab Results endpoint times out, the main dashboard still loads with the five clinical cards.
* **Rollback Plans:** If the modernization frontend breaks, the team switches the DNS or reverse proxy back to the legacy PHP dashboard directory.
* **Incident Response:** A failed auth lookup triggers a clear redirect back to the login screen with a “Session expired. Please log in again.” message.

---

# 18. Compliance & Regulatory Considerations

* **HIPAA:** This frontend is built with HIPAA awareness. It does not store PHI. It does not persist PHI to logs. It uses encrypted transmission. A formal HIPAA assessment and Business Associate Agreement (BAA) with Railway/Vercel would be required before production clinical use.
* **GDPR:** Patient data is not tracked or profiled.
* **SOC2:** The CI/CD pipeline is visible and auditable.
* **Indigenous Data Governance:** As a sovereign-capable architecture, the system defers all data use and retention policies to the OpenEMR backend it connects to.
* **Internal Governance Policies:** All AI-generated code is reviewed by a human and governed by §8 Agent Council Review (ACR), the **Planner Agent** / **Verification Agent** / **Security Audit Agent** gates, and [`.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc`](../.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc).

---
# 19. Component Inventory & Directory Structure

In this OpenEMR fork, the layout below lives under the **`frontend/`** directory (see repository governance). Paths shown are relative to `frontend/`.

## 19.1 Directory Layout
/
├── .env.local # NEVER committed
├── next.config.js
├── tailwind.config.ts
├── instrumentation.ts # Next.js hook: starts Langfuse OTEL export when DASHBOARD_LANGFUSE_ENABLE + LANGFUSE_* (Track B)
├── lib/
│ ├── observability/ # Track B Langfuse (optional): processor singleton, env gates, opaque user/session hashing, FHIR trace metadata
│ │ ├── dashboard-langfuse-processor.ts
│ │ ├── is-dashboard-langfuse-enabled.ts
│ │ ├── should-trace-fhir-proxy.ts
│ │ ├── fhir-proxy-trace-metadata.ts
│ │ └── langfuse-opaque-ids.ts # SHA-256 digests with LANGFUSE_ID_SALT (aligns with Track A)
│ ├── fhir/
│ │ ├── client.ts # Authenticated FHIR fetch wrapper
│ │ ├── schemas/ # JSON schemas from OpenEMR swagger
│ │ │ ├── patient.schema.json
│ │ │ ├── allergy-intolerance.schema.json
│ │ │ ├── condition.schema.json
│ │ │ ├── medication-request.schema.json
│ │ │ ├── care-team.schema.json
│ │ │ └── observation.schema.json
│ │ └── types.ts # Generated TypeScript types from schemas
│ ├── auth/
│ │ ├── auth.config.ts # next-auth configuration
│ │ └── middleware.ts # Route protection
│ └── utils/
│ ├── cn.ts # Tailwind class merge utility
│ └── format.ts # Date, name, MRN formatters
├── hooks/
│ ├── use-fhir.ts # Base React Query wrapper
│ ├── use-patient.ts
│ ├── use-allergies.ts
│ ├── use-problem-list.ts
│ ├── use-medications.ts
│ ├── use-prescriptions.ts
│ ├── use-care-team.ts
│ └── use-lab-results.ts
├── components/
│ ├── ui/ # shadcn/ui generated components
│ ├── layout/
│ │ ├── dashboard-shell.tsx # Main authenticated layout
│ │ └── patient-banner.tsx # Persistent patient identity bar
│ ├── cards/
│ │ ├── clinical-card.tsx # Reusable card wrapper (loading/error/empty)
│ │ ├── allergies-card.tsx
│ │ ├── problem-list-card.tsx
│ │ ├── medications-card.tsx
│ │ ├── prescriptions-card.tsx
│ │ ├── care-team-card.tsx
│ │ └── lab-results/
│ │ ├── lab-results-tabs.tsx # Tab container
│ │ ├── recent-tab.tsx
│ │ ├── chemistry-tab.tsx
│ │ └── hematology-tab.tsx
│ └── shared/
│ ├── loading-skeleton.tsx
│ ├── error-fallback.tsx
│ └── empty-state.tsx
├── app/
│ ├── layout.tsx # Root layout, providers
│ ├── page.tsx # Redirect to /login or /dashboard
│ ├── login/
│ │ └── page.tsx # OIDC redirect trigger
│ ├── api/
│ │ ├── auth/
│ │ │ └── [...nextauth]/route.ts # next-auth API route
│ │ └── fhir/
│ │ └── [...path]/route.ts # FHIR proxy; optional Langfuse span fhir-proxy-get + propagateAttributes
│ └── dashboard/
│ └── patient/
│ └── [id]/
│ ├── page.tsx # Server component, fetches patient
│ └── loading.tsx # Dashboard-level skeleton
└── types/
└── next-auth.d.ts # Session type augmentation

---
## 19.2 Component Dependency Graph

app/dashboard/patient/[id]/page.tsx
├── components/layout/dashboard-shell.tsx
│ └── components/layout/patient-banner.tsx (receives Patient resource)
├── components/cards/clinical-card.tsx (generic wrapper)
│ ├── components/cards/allergies-card.tsx
│ ├── components/cards/problem-list-card.tsx
│ ├── components/cards/medications-card.tsx
│ ├── components/cards/prescriptions-card.tsx
│ ├── components/cards/care-team-card.tsx
│ └── components/cards/lab-results/lab-results-tabs.tsx
│ ├── components/cards/lab-results/recent-tab.tsx
│ ├── components/cards/lab-results/chemistry-tab.tsx
│ └── components/cards/lab-results/hematology-tab.tsx
├── components/shared/loading-skeleton.tsx
├── components/shared/error-fallback.tsx
└── components/shared/empty-state.tsx

---

**Generation Order (Track B roles — respect this dependency order):**

1. **auth-implementer:** `lib/auth/auth.config.ts` (and related auth routes).
2. **fhir-client-builder:** `lib/fhir/types.ts`, client, schemas; then `hooks/use-fhir.ts` (all other hooks depend on this).
3. **fhir-client-builder:** Individual resource hooks (`use-patient.ts`, `use-allergies.ts`, etc.).
4. **card-component-builder:** `components/shared/*` (loading-skeleton, error-fallback, empty-state).
5. **card-component-builder:** `components/cards/clinical-card.tsx` (all cards depend on this).
6. **card-component-builder:** Individual cards + lab results tabs.
7. **layout-shell-builder:** `components/layout/patient-banner.tsx`.
8. **layout-shell-builder:** `components/layout/dashboard-shell.tsx`.
9. **layout-shell-builder:** `app/` pages (route structure, wiring).

**deployment-verifier** add Playwright, CI config, and integration checks after the application graph above is stable.

---

## 19.3 Environment Variables

```bash
# .env.local (NEVER COMMIT THIS FILE)
AUTH_SECRET="openssl rand -hex 32"
AUTH_OPENEMR_ID="your-oauth2-client-id"
AUTH_OPENEMR_SECRET="your-oauth2-client-secret"
OPENEMR_BASE_URL="https://your-openemr-instance.com"
OPENEMR_OAUTH2_ISSUER="https://your-openemr-instance.com/oauth2/default"
NEXTAUTH_URL="http://localhost:3000"

# Optional — Track B Langfuse (same LANGFUSE_* keys as Clinical Co-Pilot; see docker/agentforge-railway/env.langfuse.example)
# DASHBOARD_LANGFUSE_ENABLE=1
# LANGFUSE_PUBLIC_KEY=pk-lf-...
# LANGFUSE_SECRET_KEY=sk-lf-...
# LANGFUSE_BASE_URL=https://hipaa.cloud.langfuse.com
# LANGFUSE_ID_SALT=...   # recommended; shared with Track A for consistent opaque id policy
```

---

## 19.4 FHIR API Contract
All hooks call endpoints relative to OPENEMR_BASE_URL:

Hook	FHIR Endpoint
usePatient(id)	/apis/default/fhir/Patient/${id}
useAllergies(id)	/apis/default/fhir/AllergyIntolerance?patient=${id}
useProblemList(id)	/apis/default/fhir/Condition?patient=${id}&clinical-status=active
useMedications(id)	/apis/default/fhir/MedicationRequest?patient=${id}&status=active
usePrescriptions(id)	/apis/default/fhir/MedicationRequest?patient=${id}&status=active&intent=order
useCareTeam(id)	/apis/default/fhir/CareTeam?patient=${id}&status=active
useLabResults(id)	/apis/default/fhir/Observation?patient=${id}&category=laboratory

---

# 20. Future Expansion

* **Roadmap Assumptions:** The primary goal is proving the Strangler-Fig pattern for OpenEMR.
* **Extensibility Goals:** Other patient-facing tabs (encounter history, vitals) can be added by creating new `components/cards/` and a corresponding `hooks/use-fhir-{resource}.ts`.
* **Interoperability Goals:** The strictly typed FHIR client can be extracted into an open-source package for other OpenEMR frontend projects.
* **Migration Strategies:** The component library is designed to be ported to React Native for a future mobile experience if desired.

---



# 21. Final Engineering Position

This system is designed according to:

* **MoniGarr Operating Model (M.O.M.)** – Human accountability, enterprise readiness, brownfield respect.
* **MoniGarr Intelligence-Led Engineering (M.I.L.E.)** – AI accelerates every phase of development, documentation, and verification without replacing human oversight.
* **Echelon Enterprise Engineering Standards** – A complete, defendable architectural posture that proves this is a production-grade migration, not a prototype.

The system prioritizes:

* **human accountability:** Every OAuth2 flow and clinical card is human-verified.
* **sovereign engineering:** The upstream OpenEMR repository remains the unmodified, sovereign source of truth.
* **operational continuity:** A clinician using the new dashboard should experience no disruption from the old.
* **enterprise reliability:** Error boundaries, graceful fallbacks, and instant rollback are built-in, not bolted-on.
* **scalable integration with Track A copilot services:** The dashboard architecture can coexist with Clinical Co-Pilot without embedding an LLM orchestration stack in Next.js. **LangChain and LangGraph are explicitly out of program scope** for AgentForge orchestration (**ADR-007**); Track A remains on the OpenAI tool loop with **Langfuse** as the in-program observability option when enabled, and **Track B** may use the **same Langfuse project** for FHIR-proxy span metadata when `DASHBOARD_LANGFUSE_ENABLE` is on.
* **long-term maintainability:** A Next.js + shadcn/ui stack is the industry standard and well-understood by any modern frontend team.

**AI accelerates engineering.**  
**Humans remain accountable.**  
**Systems remain governable.**