# AgentForge Clinical Co-Pilot — Technical architecture

**Upstream:** [openemr/openemr](https://github.com/openemr/openemr/tree/master)  
**License context:** OpenEMR is GPL-3.0-or-later; this fork’s additive documentation does not change upstream licensing.

---

## One-page summary (~500 words)

OpenEMR is a mature monolithic electronic health record with multiple architectural eras: a growing PSR-4 `OpenEMR\` tree under `src/`, legacy procedural code under `library/` and `interface/`, a PSR-11 bootstrap (`bootstrap.php`) used by the modern front controller (`public/index.php`), and Laminas MVC modules for selected subsystems. The Clinical Co-Pilot is designed to **sit inside that reality** rather than replace it: the agent’s **trust boundary** is the **same authenticated PHP request** as the rest of the clinician UI, using **in-process** calls into OpenEMR’s **authorization and data-access patterns**, not a parallel “super-user” FHIR client for this sprint.

The user is a **high-volume primary care physician** (see [USERS.md](USERS.md)). The highest-value window is **between exam rooms**, when the physician must reconstruct context in under a minute. That imposes **strict latency goals** and a **speed-versus-completeness** tradeoff: the system should return a **thin, verifiable briefing** quickly, support **follow-up questions**, and **label uncertainty** when the chart is incomplete.

**Large language model:** The sprint uses the **OpenAI HTTP API** against **synthetic demo chart content only** (per program instructions). Production-grade deployment would require **Business Associate Agreements**, **minimum necessary** payloads, **subprocessor governance**, and **verified** data retention and training exclusions—documented as a forward path, not assumed solved by the class environment.

**Tooling strategy:** Tools are **PHP functions or thin controller handlers** that gather **structured patient data** by reusing existing OpenEMR services (for example `OpenEMR\Services\PatientService` and other domain services under `src/Services/`) **only after** the request is operating under a **valid staff session** and **active patient context**, with the same **access checks** the UI would rely on. Tools return **JSON** suitable for model consumption (field-limited, no full chart dumps by default).

**Verification:** The model emits a **structured response schema** pairing **factual clinical statements** with **citation handles** referencing tool output (record IDs, table row references, or opaque server-side keys that resolve only server-side). A **PHP verification gate** strips or downgrades any **uncited factual claim** before the UI renders it. **Domain constraints** (e.g. hard limits on dosing suggestions, or prohibition on definitive new diagnoses) are enforced in **PHP rules**, not delegated to the model.

**Observability:** The architecture compares **four** patterns: (1) **Langfuse** or similar SaaS with **strict redaction** and no raw PHI; (2) **self-hosted** trace store inside the clinic VPC; (3) a **dedicated database table** storing redacted step metadata; (4) **application JSON logs** only. For the sprint, the **default recommendation** is **(4) plus optional (3)** for demo-friendly audit trails, moving to **(2)** before any real PHI.

**Failure modes:** Tool timeouts, partial records, and model **malformation** must yield **degraded but honest** responses—never silent wrong facts. The UI shows **which tools ran**, what **failed**, and what **could not be verified**.

**Evolution:** If the product graduates beyond synthetic demos, the documented **production** path is **OAuth2-scoped FHIR or Standard API** tools with explicit client scoping—without changing the core principle that **authorization is never delegated to the LLM**.

Agent tools execute in-process under the authenticated OpenEMR session, delegating to the same authorization and data-access layers as the clinical UI; we are not exposing a separate broad FHIR client for this sprint, because of this project’s scope, and we document the production path to OAuth2-scoped tools if the agent were promoted beyond synthetic data.

---

## Table of contents

1. [Repository scope (Week 1 / PRD)](#repository-scope-week-1--prd)  
2. [Goals and non-goals](#goals-and-non-goals)  
3. [Alignment with USERS.md](#alignment-with-usersmd)  
4. [High-level system diagram](#high-level-system-diagram)  
5. [Trust boundaries](#trust-boundaries)  
6. [Request path and OpenEMR integration](#request-path-and-openemr-integration)  
7. [Tool layer](#tool-layer)  
8. [LLM integration (OpenAI)](#llm-integration-openai)  
9. [Verification pipeline](#verification-pipeline)  
10. [Observability options](#observability-options)  
11. [Evaluation hooks](#evaluation-hooks)  
12. [AI cost analysis (planning)](#ai-cost-analysis-planning)  
13. [Failure modes and degradation](#failure-modes-and-degradation)  
14. [Deployment (TBD checklist)](#deployment-tbd-checklist)  
15. [References within this repository](#references-within-this-repository)

---

## Repository scope (Week 1 / PRD)

**Single source of truth in this fork:** Week 1 AgentForge planning, hard-gate documentation, deployment URL for submissions, and summaries of eval intent and AI cost **live in this repository**—primarily in [AUDIT.md](AUDIT.md), [USERS.md](USERS.md), and this file—alongside [`Documentation/PRD_Week1_AgentForge.pdf`](Documentation/PRD_Week1_AgentForge.pdf). Avoid parallel specs in external tools that can drift from what graders see in GitHub.

**Capability boundary:** [USERS.md](USERS.md) defines the **only** user problems the agent may address; every tool, prompt, and UI behavior in later implementation must **trace to a use case** there. The audit ([AUDIT.md](AUDIT.md)) is the **input** to this architecture plan (PRD Stage 5); implementation should not outrun audited risks.

**PRD checkpoint → document map**

| PRD stage / gate | Where it is satisfied in-repo |
|------------------|-------------------------------|
| Stage 3 — Audit | [AUDIT.md](AUDIT.md) (five audit areas + ~500 word summary) |
| Stage 4 — Users & use cases | [USERS.md](USERS.md) (narrow user + use cases + why conversational) |
| Stage 5 — Agent integration plan | This file (~500 word summary + technical sections) |
| Observability minimum questions | [Observability options](#observability-options) |
| Evaluation / test suite | [Evaluation hooks](#evaluation-hooks); detailed fixtures may live under `tests/` when code exists |
| Submitted deployed URL | [Deployment (TBD checklist)](#deployment-tbd-checklist) — canonical URL field |

**Course deliverables vs. these three files:** The PRD also names a fork **README** (setup guide), an **eval dataset with results**, and **AI cost analysis**. If instructors require those as **separate committed files**, add or extend them outside this trio and keep one-line pointers here. If not, treat the sections [Local setup and deployment pointers](#local-setup-and-deployment-pointers), [Evaluation hooks](#evaluation-hooks), and [AI cost analysis (planning)](#ai-cost-analysis-planning) as the in-repo record.

---

## Goals and non-goals

**Goals**

- **Sub-minute** orientation for the PCP use cases in [USERS.md](USERS.md).
- **Citation-backed** clinical statements for chart-derived facts.
- **Server-side-only** orchestration (no PHI-bearing tool calls from untrusted browser code).
- **Additive** implementation: prefer new module routes and services over forking core legacy files.

**Non-goals (sprint)**

- Autonomous clinical decision-making or autonomous ordering.
- Full FHIR tool chain **inside** the sprint (see summary sentence above).
- Training or fine-tuning a model on customer data.

---

## Alignment with USERS.md

| US case | Architectural components |
|---------|----------------------------|
| UC1 Visit framing | Encounters + problems tools; summarization prompt; verification |
| UC2 Deltas | Labs/meds tools with “since last visit” windowing; uncertainty labels |
| UC3 Pre-room checks | Rule engine + cited suggestions; explicit “verify with patient” copy |

---

## High-level system diagram

```mermaid
flowchart TB
  subgraph browser [Browser]
    UI[CoPilot_UI]
  end
  subgraph openemr [OpenEMR_PHP_Session]
    EP[Agent_Endpoint]
    ACL[Session_ACL_PatientContext]
    TL[Tool_Layer]
    SV[Domain_Services_src_Services]
    VG[Verification_Gate]
  end
  subgraph external [External]
    OAI[OpenAI_API]
  end
  UI -->|HTTPS_same_origin| EP
  EP --> ACL
  EP --> TL
  TL --> SV
  EP --> OAI
  OAI -->|structured_model_output| VG
  TL -->|tool_JSON_citations| VG
  VG -->|safe_payload| UI
```

---

## Trust boundaries

| Boundary | Responsibility |
|----------|------------------|
| Browser ↔ OpenEMR | Standard OpenEMR session cookies / CSRF patterns for new endpoints. |
| OpenEMR ↔ OpenAI | **Minimum necessary** JSON; TLS; API key in server config only; **no PHI in client-accessible logs**. |
| Model ↔ User | **No** unverified factual text; verification gate is mandatory. |

---

## Request path and OpenEMR integration

Modern requests may enter through [`public/index.php`](public/index.php), which loads the PSR-11 container from [`bootstrap.php`](bootstrap.php) and delegates routing via `OpenEMR\BC\FallbackRouter` to legacy scripts. Agent endpoints should follow **the same authentication bootstrap** as other privileged PHP entrypoints (exact file(s) depend on implementation choice: new route under `interface/` or custom module pattern).

**Principle:** The agent never receives a **wider** data scope than the logged-in user for the **currently selected patient**.

---

## Tool layer

**Design**

- Each tool: **name**, **input schema** (patient id, date windows, optional question), **output JSON**, **max runtime**, **idempotent** where possible.
- Tools call **`OpenEMR\Services\*`** or other approved internal APIs—not raw SQL from the model.

**Initial tool candidates (illustrative)**

| Tool | Purpose | Typical service anchor |
|------|---------|-------------------------|
| `patient_core` | Demographics, PCCP, identifiers | `OpenEMR\Services\PatientService` |
| `active_meds` | Medication list snapshot | Domain medication services |
| `recent_labs` | Windowed labs | Lab result services |
| `recent_encounters` | Visit list / reasons | Encounter-related services |

*(Exact method names should be pinned when code is written; PatientService is the canonical patient row access pattern in `src/Services/PatientService.php`.)*

---

## LLM integration (OpenAI)

- **Transport:** HTTPS to OpenAI’s API from PHP (e.g. Guzzle — already a project dependency per `composer.json`).
- **Configuration:** API key from environment or secured config **outside** webroot; never embedded in frontend.
- **Prompting:** System prompt encodes **citation requirements**, **refusal rules**, and **scope** (active patient only).
- **BAA / enterprise:** Document in [AUDIT.md](AUDIT.md) compliance section; use **Azure OpenAI** or **OpenAI Enterprise** if a real hospital required a single named BAA—sprint may use developer API **only with synthetic data**.

---

## Verification pipeline

1. **Plan:** Model may emit a hidden chain-of-thought **only if** your vendor/policy allows; otherwise use **structured tool plans** without chain-of-thought storage.
2. **Tool execution:** Server runs tools; results stored **in memory** for the request (or short-lived server-side cache keyed by session).
3. **Model answer:** Must conform to JSON schema: `{ "statements": [ { "text", "citations": ["..."] } ], "uncertainties": [...] }`.
4. **PHP gate:** Drop statements whose citations do not resolve to tool JSON; convert borderline cases to **uncertainty** strings.
5. **Rule engine:** Secondary pass for **forbidden content** (e.g. definitive new cancer diagnosis strings) regardless of citations.

---

## Observability options

| Option | Pros | Cons | PHI risk |
|--------|------|------|----------|
| **A. Langfuse (cloud)** | Rich traces, token/cost dashboards | Third-party subprocessors | **High** if raw prompts logged |
| **B. Self-hosted trace DB** | Control, VPC-only | Ops burden | **Medium**—must redact |
| **C. OpenEMR DB audit table** | Stays in app boundary | Schema + migration work | **Low** if redacted columns only |
| **D. PHP JSON logs** | Fastest sprint path | Weaker UI | **Low** with redaction |

**Sprint recommendation:** **D**, optionally **C** for structured step timing; migrate toward **B** before production PHI.

**Minimum questions observability must answer (PRD):**

- What did the agent do, **in order**?
- How long did each step take?
- Which tools failed and why?
- Token usage and **estimated cost** per request?

**PRD bar (“real, wired in, used”):** Observability is not satisfied by installing a library alone. The chosen approach must emit structured events or logs on **actual** agent requests, be **consulted** when debugging (e.g. step timing, tool errors), and inform cost/token discipline—not a dormant dependency.

---

## Evaluation hooks

**Intent (PRD):** The suite must be **defensible**—not only happy paths. It should surface failure modes that matter in clinical settings: missing chart fields, ambiguous user phrasing, tool timeouts, malformed model JSON, and **unauthorized** data access attempts.

| Category | What “pass” means (examples) |
|----------|----------------------------|
| **Schema / verification** | Output JSON validates; uncited factual statements are stripped or downgraded. |
| **Tool + gate integration** | Given golden tool JSON fixtures, rendered user text matches expected safe payload. |
| **Authorization** | User/session A cannot obtain patient B’s tool payloads (negative tests). |
| **Resilience** | Simulated OpenAI errors or empty tool results yield explicit degradation, not silent invention. |

**Mechanisms:** Unit tests for JSON schema and verification gate; integration tests with **mocked** OpenAI; synthetic fixtures only (**no real PHI**).

**Results log (update when runs exist):** Record date, command (e.g. `phpunit` target), pass/fail counts, and notable regressions in a bullet list here or in CI output linked from the fork. Until tests land, this subsection remains the **planned** eval contract.

---

## AI cost analysis (planning)

**Development spend:** Track actual API spend during integration (model choice, average tools per request, tokens in/out). Update this subsection with rough monthly dev totals when available.

**Projected load (illustrative axes—replace with measured averages):** Assume *N* concurrent clinicians, *R* requests per clinician per hour, and *T* total input+output tokens per request (including tool JSON). Cost scales with *N × R × T ×* price-per-token; tool-heavy flows increase *T* faster than chat-only flows.

| Scale (active clinical users) | Architectural implication |
|------------------------------|----------------------------|
| **~100** | Single-region OpenEMR + rate limits; shared API key pool with per-tenant budgets if multi-site. |
| **~1K** | Queue or throttle agent requests; cache **non-PHI** or redacted short-lived briefings where policy allows; consider reserved throughput / enterprise API. |
| **~10K** | Regional deployment, aggressive **minimum-necessary** prompts, model tiering (smaller model for routing), batch where safe—not linear “token × users” without redesign. |
| **~100K** | Dedicated inference contracts, possible **on-VPC** or Azure OpenAI–class deployments; observability and cost allocation per site/department. |

This is **not** “cost-per-token × users” alone; bounded tools, verification passes, and caching policy dominate at scale.

---

## Failure modes and degradation

| Failure | User-visible behavior |
|---------|-------------------------|
| Tool timeout | “Labs unavailable—showing problems only.” |
| Empty chart section | Explicit **missing data** label. |
| Model JSON invalid | One automatic **repair** attempt; else safe error. |
| OpenAI outage | Cached last-good briefing **if policy allows**; else clear outage message. |

---

## Deployment (TBD checklist)

**Canonical deployed application URL (PRD submissions):**  
`TBD` — replace at each checkpoint (MVP, Early Submission, Final) with the **publicly reachable** URL of this fork’s deployment; keep in sync with what you submit to the course.

**Hosting not yet selected.** Before going live even with synthetic data, complete:

- [ ] TLS certificate and HTTPS-only cookies  
- [ ] Secrets management (API keys)  
- [ ] Admin password rotation / demo banner  
- [ ] Log rotation and **redaction** rules  
- [ ] Backup policy for DB (if storing traces)  
- [ ] Rollback: prior container image or release tag  

### Local setup and deployment pointers

- **Local OpenEMR:** Follow upstream guidance (e.g. Docker or stack docs from [openemr/openemr](https://github.com/openemr/openemr)); fork-specific env vars or compose overrides should be **documented here in bullet form** as you stabilize them so Week 1 “Run it locally” stays traceable in-repo.  
- **Public deploy:** Same stack family as intended for the final agent reduces surprise; record provider and **runtime** (PHP version, extensions) briefly here once chosen.

---

## References within this repository

| Topic | Location |
|--------|----------|
| Front controller + DI bootstrap | [`public/index.php`](public/index.php), [`bootstrap.php`](bootstrap.php) |
| Legacy routing bridge | [`src/BC/FallbackRouter.php`](src/BC/FallbackRouter.php) |
| Modern services | [`src/Services/`](src/Services/) (e.g. [`src/Services/PatientService.php`](src/Services/PatientService.php)) |
| API / OAuth / FHIR (future path) | [`Documentation/api/`](Documentation/api/) |
| Contributor / quality bar | [`CLAUDE.md`](CLAUDE.md) |
| Security reporting | [`.github/SECURITY.md`](.github/SECURITY.md) |
| PRD | [`Documentation/PRD_Week1_AgentForge.pdf`](Documentation/PRD_Week1_AgentForge.pdf) |

---

## Document control

| Field | Value |
|--------|--------|
| **Project** | AgentForge — Clinical Co-Pilot |
| **Companion documents** | [USERS.md](USERS.md), [AUDIT.md](AUDIT.md) |
| **PRD (Week 1)** | [`Documentation/PRD_Week1_AgentForge.pdf`](Documentation/PRD_Week1_AgentForge.pdf) — submission table lists `./USER.md`; this fork uses **`./USERS.md`** at repo root (align with graders if needed). |
