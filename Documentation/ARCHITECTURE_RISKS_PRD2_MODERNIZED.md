# ============================================================================
# ARCHITECTURE_RISKS_PRD2_MODERNIZED.md
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
#   Active Risk Register
#
# Author:
#   Monica Peters
#
# Created:
#   2026-05-06
#
# Last Updated:
#   2026-05-07
#
# Classification:
#   Internal — Contains architectural vulnerability analysis
#
# ============================================================================
#
# PURPOSE
# ----------------------------------------------------------------------------
# Comprehensive risk register for the OpenEMR Patient Dashboard modernization.
# Every risk is traceable to a specific architectural decision documented in
# ARCHITECTURE_PRD2_MODERNIZED.md. This document serves:
#
# - Pre-mortem analysis
# - Agent Council Review (ACR) input
# - Deployment gate criteria
# - Operational continuity planning
#
# Risk philosophy: We identify risks before they become incidents.
# No risk is too small to document; undocumented risks become unmanaged failures.
#
# ============================================================================
```

---

# 1. Risk Classification Framework

| Severity | Definition                                                         | Response Required                   |
| -------- | ------------------------------------------------------------------ | ----------------------------------- |
| **P0**   | Patient safety risk or complete dashboard unavailability           | Immediate mitigation before merge   |
| **P1**   | Feature degradation or security boundary violation                 | Mitigation required before deploy   |
| **P2**   | Developer experience impact or technical debt accumulation         | Documented accept or scheduled fix  |
| **P3**   | Cosmetic, future concern, or theoretical                           | Monitored; no immediate action      |

| Likelihood | Definition                          |
| ---------- | ----------------------------------- |
| **High**   | Expected to occur within the sprint |
| **Medium** | Plausible under specific conditions |
| **Low**    | Unlikely but possible               |

---

# 2. Risk Register

---

## R-001: OAuth2 Token Exposure via Client-Side JavaScript

**Severity:** P0  
**Likelihood:** Medium  
**Category:** Security / Authentication  
**Source Decision:** Use of next-auth with HTTP-only cookies (ARCHITECTURE_PRD2_MODERNIZED.md §7.1, §9)

### Description
If the `next-auth` session callback is misconfigured to forward the `accessToken` to the client-side session object (instead of keeping it server-side), the OpenEMR access token could be exposed to browser JavaScript. This would violate the security boundary and potentially allow token exfiltration via XSS.

### Trigger Condition
**auth-implementer** (Track B roster) produces or edits `auth.config.ts` such that `accessToken` lands in the `session()` callback without keeping it server-only, OR a developer manually adds it to debug FHIR calls from the browser. Generic “agent-generated” auth changes are the usual path; human error has the same effect.

### Roster tie-in (documentation richness)
Per **AGENT-TEAM-PRD2-Subagents-Roster.mdc**, **Security Audit Agent** is the review-only gate on **auth-implementer** output specifically for token exposure (**R-001**). This does not introduce new role titles beyond the published roster.

### Impact
- Full API access as the authenticated user.
- Potential PHI exposure if the token is exfiltrated.
- HIPAA violation (unauthorized access to ePHI).

### Mitigation
1. **Strict code review gate:** The `session()` callback in `frontend/lib/auth/auth.config.ts` must NOT expose `accessToken` to the client.
2. **Security Audit Agent review:** Before merge, **Security Audit Agent** reviews **auth-implementer** changes to `lib/auth/`, `app/api/auth/`, and login flows for R-001 (token never in client session shape or browser-visible state).
3. **Server-side FHIR fetching only:** All FHIR data retrieval happens in React Server Components or Next.js API routes — never in client components with a raw token.
4. **Automated test:** Playwright test asserts `window.__NEXT_DATA__` does not contain an access token pattern.
5. **ARCHITECTURE_PRD2_MODERNIZED.md already specifies:** "OAuth2 token never exposed to browser JavaScript (HTTP-only session cookie)" — §5 Non-Functional Goals > Security. **auth-implementer** and all agents must not violate this.

### Residual Risk After Mitigation
Low. The architecture deliberately routes all API calls through server-side code. Accidental exposure requires a developer to actively override this pattern.

---

## R-002: CORS Restriction Forces Proxy Complexity

**Severity:** P1  
**Likelihood:** High  
**Category:** Integration / Brownfield Compatibility  
**Source Decision:** Framework choice (Next.js) interacting with legacy PHP backend (ARCHITECTURE_PRD2_MODERNIZED.md §6, §10.1)

### Description
OpenEMR may not include the new frontend's origin in its CORS `Access-Control-Allow-Origin` headers. If the browser cannot directly call the FHIR API, all requests must be proxied through Next.js API routes. This adds latency (double-hop), server load, and a new failure surface.

### Trigger Condition
The deployed frontend origin (e.g., `https://patient-dashboard.vercel.app`) is not in OpenEMR's CORS whitelist. Attempted direct browser fetch to `OPENEMR_BASE_URL/apis/default/fhir/Patient/123` returns a CORS error.

### Impact
- Clinical cards show error states instead of data.
- All FHIR traffic forced through the Vercel/Railway proxy, increasing serverless function execution time and cost.
- Proxy becomes a single point of failure for all data display.

### Mitigation
1. **Proxy layer pre-built:** `frontend/app/api/fhir/[...resource]/route.ts` is part of the standard directory structure (ARCHITECTURE_PRD2_MODERNIZED.md §19.1). It is not an emergency add-on.
2. **Environment variable toggle:** `FHIR_PROXY_MODE=auto|always|never` in `.env.local` allows switching between direct and proxied requests without code changes.
3. **Latency budget:** The proxy adds <50ms overhead for same-region deployments (Vercel + OpenEMR host in same cloud region).
4. **OpenEMR CORS configuration (optional, not required):** If the client permits, add the frontend origin to OpenEMR's CORS settings. This is a backend change and is explicitly optional — the proxy is the primary mitigation.

### Residual Risk After Mitigation
Medium. The proxy works but introduces a dependency on the Next.js server for every data fetch. If the proxy function cold-starts (serverless), initial load may exceed 1 second. Acceptable for V1; optimize with Edge Functions if needed.

---

## R-003: FHIR Schema Drift Causes Silent Rendering Failures

**Severity:** P1  
**Likelihood:** Medium  
**Category:** Integration / Data Contract  
**Source Decision:** Consuming OpenEMR's FHIR API as an immutable contract (ARCHITECTURE_PRD2_MODERNIZED.md §10.1, §19.4)

### Description
The OpenEMR backend may return FHIR resources with fields or structures that differ from the embedded JSON schemas (`lib/fhir/schemas/*.json`). This can happen due to:
- OpenEMR version upgrades on the backend (unrelated to this project).
- Custom modules or extensions modifying FHIR output.
- Environment-specific configuration (different FHIR versions enabled).

If a component expects `patient.name[0].given[0]` and the field is `patient.name[0].text` (or absent), the component will crash or render blank.

### Trigger Condition
Frontend connects to an OpenEMR instance running a different FHIR version than the schemas were extracted from, OR a custom module adds an unexpected `extension` array that breaks the type assumptions.

### Impact
- Clinical card renders empty or crashes (ErrorBoundary catches it, shows "Retry" — but data is never displayed).
- Clinician loses access to patient information.
- Repeated failures erode trust in the new dashboard.

### Mitigation
1. **Runtime validation with `zod`:** Every `use-fhir-*.ts` hook validates the API response against a Zod schema derived from the embedded FHIR schema before passing data to the component. Invalid fields are flagged, not silently swallowed.
2. **Defensive field access:** All render code uses optional chaining: `patient?.name?.[0]?.given?.[0] ?? "Name unavailable"`.
3. **Schema embedding:** The FHIR schemas are committed to the repository and version-pinned to the target OpenEMR version.
4. **Logging on mismatch:** If Zod validation fails, the proxy layer logs the mismatched field (without PHI) to structured logs for investigation.

### Residual Risk After Mitigation
Low-Medium. Zod catches structural drift before it reaches the component. However, semantic drift (e.g., a new coding system for allergies that changes what constitutes an "active" allergy) might still pass validation but display incorrect clinical information. Clinical validation by a human is required before production use.

---

## R-004: AI-Generated Component Contains Hallucinated FHIR Fields

**Severity:** P2 (escalates to P1 if deployed without review)  
**Likelihood:** High  
**Category:** AI-Native Engineering / Code Quality  
**Source Decision:** AI-First development with Cursor agent generation (ARCHITECTURE_PRD2_MODERNIZED.md §7, §12)

### Description
Cursor may generate a component that references a FHIR field that does not exist in the actual OpenEMR FHIR API response. For example, `allergy.clinicalStatus` might be generated as `allergy.status` based on common patterns in other FHIR implementations. This is a hallucination — syntactically valid TypeScript, semantically incorrect.

### Trigger Condition
Agent generates a component without the embedded FHIR schema as context, OR the FHIR schema file in the repo is outdated, OR the agent infers a field from a generic React pattern rather than the actual schema.

### Impact
- Component compiles successfully but renders `undefined` for the hallucinated field.
- The field is silently absent from the card.
- If the missing field is clinically significant (e.g., allergy severity), the dashboard presents incomplete information.

### Mitigation
1. **FHIR schemas embedded in repo:** `lib/fhir/schemas/*.json` are committed before any component generation. Agents MUST reference these, not infer fields.
2. **Zod validation catches missing fields at runtime:** If a component tries to render a field the API didn't return (even if the type allows undefined), the empty state or fallback text appears instead of a crash.
3. **Human verification gate:** Every generated card component is manually compared against the PHP dashboard for the same patient. Missing fields are visually obvious.
4. **TypeScript strict mode:** `tsconfig.json` has `strict: true`. Optional FHIR fields require explicit optional chaining. This forces the agent (and developer) to handle `undefined` cases.

### Residual Risk After Mitigation
Low. The combination of embedded schemas, Zod validation, TypeScript strict mode, and visual verification makes hallucinated fields highly detectable. No hallucinated field should survive to deployment.

---

## R-005: Stale FHIR Data Displayed Due to React Query Caching

**Severity:** P2  
**Likelihood:** Medium  
**Category:** Data Freshness / Clinical Accuracy  
**Source Decision:** React Query with 30-second stale-while-revalidate TTL (ARCHITECTURE_PRD2_MODERNIZED.md §5, §16)

### Description
React Query serves cached data instantly (fast UX) and refetches in the background. If a clinician views a patient dashboard, another clinician updates an allergy in OpenEMR, and the first clinician returns to the dashboard within the 30-second cache window, they will see the old allergy list. This is a temporary stale data display.

### Trigger Condition
Two clinicians accessing the same patient record within a 30-second window where one makes an update that the other's dashboard hasn't refetched yet.

### Impact
- Clinician makes a decision based on slightly stale data.
- If the update was critical (e.g., new severe allergy documented), a 30-second delay in visibility could have clinical consequences in an emergency.

### Mitigation
1. **Window refocus triggers refetch:** React Query's `refetchOnWindowFocus: true` (default) means that if the clinician tabs away and returns, data is immediately refetched regardless of cache age.
2. **Manual refresh button on every card:** Each `ClinicalCard` includes a subtle refresh icon that forces an immediate refetch.
3. **Stale indicator:** Cards display a small "Data may be up to 30s old" timestamp when serving from cache (non-intrusive, informational).
4. **30-second TTL is conservative for non-emergency workflows:** For the clinical cards defined in the PRD (allergies, problem list, medications, care team), these data sets change infrequently. The additional section (Lab Results) has a shorter 10-second TTL because results arrive more dynamically.
5. **Future enhancement (out of scope):** WebSocket or SSE connection from OpenEMR to push real-time updates. Documented as a roadmap item.

### Residual Risk After Mitigation
Low. The combination of refetch-on-focus, manual refresh, and a visible staleness indicator gives clinicians multiple ways to ensure they're viewing current data. For the specific cards in scope (not real-time vitals monitoring), 30 seconds is clinically acceptable.

---

## R-006: Next.js Serverless Cold Start Delays Clinical Dashboard Load

**Severity:** P2  
**Likelihood:** Medium (Vercel), Low (Railway with always-on)  
**Category:** Performance / Deployment  
**Source Decision:** Deployment on Vercel (serverless) vs Railway (always-on container) (ARCHITECTURE_PRD2_MODERNIZED.md §15, §16)

### Description
If deployed on Vercel's serverless platform, the Next.js server function may cold-start after periods of inactivity. A cold start can add 500ms-2s to the initial page load, during which the clinician sees a loading state. In a clinical setting, this delay may be frustrating or, in edge cases, clinically relevant.

### Trigger Condition
First request after a period of inactivity (typically 5-10 minutes on Vercel Hobby, longer on Pro). Subsequent requests are fast (warm function reuse).

### Impact
- First clinician of the day or after a quiet period experiences a slower dashboard load.
- Loading skeleton visible for 1-3 seconds instead of near-instant.
- Not a patient safety risk (data loads correctly once warm), but a user experience degradation.

### Mitigation
1. **Railway as primary deployment target:** Railway runs the Next.js server as an always-on container. No cold starts. This is the recommended production deployment for clinical environments.
2. **Vercel Pro with minimum 1 warm instance:** If Vercel is preferred, upgrade to Pro tier and configure a minimum of 1 always-warm function. Additional cost (~$20/month).
3. **Static generation where possible:** The login page and any non-patient-specific pages are statically generated at build time (zero server load).
4. **Skeleton loaders mask the delay:** The dashboard loads a full-page skeleton immediately (client-side, zero server wait), then hydrates with data. The perceived performance is a smooth transition, not a blank screen.

### Residual Risk After Mitigation
Low. Railway eliminates cold starts entirely. If Vercel is used without Pro, the skeleton loader makes the delay feel intentional, not broken.

---

## R-007: Agent Council Review (ACR) Bypass Leads to Unreviewed AI Code in Production

**Severity:** P1  
**Likelihood:** Low  
**Category:** AI Governance / Process  
**Source Decision:** Human-in-the-loop accountability model (ARCHITECTURE_PRD2_MODERNIZED.md §7, §8)

### Description
Under time pressure (four-day delivery), the developer may skip the Agent Council Review steps defined in ARCHITECTURE_PRD2_MODERNIZED.md §8 and deploy AI-generated code without adversarial review, security analysis, or verification against the PHP dashboard. This undermines the entire accountability model.

### Trigger Condition
Deadline pressure + feature completeness achieved + "it looks correct" assumption. The developer runs `git push` without the formal ACR checklist being completed.

### Impact
- Unreviewed AI hallucination in a clinical card.
- Security misconfiguration in auth (e.g., token exposed to client).
- No traceability for which agent generated which component.
- Clinical safety risk if an allergy or medication card is incorrect.

### Mitigation
1. **ACR checklist is a merge gate:** The `prd2_af_modernized` branch has branch protection rules requiring a completed `AUDIT_PRD2_MODERNIZED.md` file to be committed alongside any component code.
2. **Minimum review surface:** Only 6 clinical cards + 1 extra section. Each card has a dedicated review slot (15 minutes each). Total review time: ~2 hours. This fits within the four-day window.
3. **Side-by-side verification script:** A Playwright test captures screenshots of the new dashboard and the original PHP dashboard for the same patient ID. A human compares them visually. This is the final gate before the "feature parity" claim.
4. **Accountability is personal:** The author (Monica Peters) signs off on each agent artifact. No anonymous AI commits.

### Residual Risk After Mitigation
Low. The process is lightweight enough to fit the timeline and mandatory enough to prevent bypass. The side-by-side screenshot comparison is the strongest single mitigation: if it looks different from the PHP dashboard, it doesn't ship.

---

## R-008: Railway/Vercel Outage Takes Down Dashboard Independent of OpenEMR Health

**Severity:** P1  
**Likelihood:** Low  
**Category:** Operational / Dependency  
**Source Decision:** External platform dependency for the new frontend (ARCHITECTURE_PRD2_MODERNIZED.md §15)

### Description
The original PHP dashboard runs on the same server as OpenEMR — if the server is up, the dashboard is up. The new frontend introduces an external dependency: Railway or Vercel. If the deployment platform experiences an outage, the new dashboard is unavailable even if OpenEMR is fully operational.

### Trigger Condition
Railway or Vercel platform outage (historically rare: Vercel had one major outage in 2023, ~2 hours; Railway has had isolated regional issues).

### Impact
- Clinicians cannot access the patient dashboard via the new frontend.
- No patient data is lost (OpenEMR backend continues to function).
- The fallback is the original PHP dashboard (if still accessible).

### Mitigation
1. **Original PHP dashboard remains accessible:** The modernization does NOT disable or remove the old dashboard. It runs alongside. The mitigation is a one-line DNS or reverse proxy change to redirect traffic back to the PHP version.
2. **Status page monitoring:** Uptime monitoring on the deployed frontend alerts the maintainer within 5 minutes of an outage.
3. **Documented rollback procedure:** The README.md includes explicit instructions for reverting to the PHP dashboard in under 5 minutes.
4. **Platform choice reflects reliability:** Railway and Vercel both have >99.9% uptime SLAs. The outage risk is comparable to any self-hosted infrastructure.

### Residual Risk After Mitigation
Low-Medium. The existence of the fallback (original PHP dashboard) means the clinical impact is "inconvenience, switch to backup" rather than "loss of access to patient data." The key operational requirement is that someone knows how to execute the rollback.

---

## R-009: Next-Auth Session Store Exhaustion Under Load

**Severity:** P3  
**Likelihood:** Low  
**Category:** Scalability / Infrastructure  
**Source Decision:** Use of next-auth with default JWT strategy (stateless) (ARCHITECTURE_PRD2_MODERNIZED.md §7.2)

### Description
The architecture uses `next-auth` with its default JWT session strategy. This means session state is stored entirely in the encrypted HTTP-only cookie — no server-side session store is required. This is stateless and infinitely scalable. However, if the project later switches to a database-backed session strategy (for administrative session revocation), a session store (e.g., Redis, PostgreSQL) would be required, introducing a new scaling concern.

### Trigger Condition
Future architectural decision to move from JWT to database sessions for centralized session management. Not triggered in the current architecture.

### Impact
- Under current architecture: None. JWT sessions are stateless.
- Under future database-session architecture: Session store could become a bottleneck if not provisioned correctly (unlikely at clinic-scale <10,000 concurrent users).

### Mitigation
1. **Stay on JWT strategy for V1:** The PRD does not require administrative session revocation. JWT sessions are sufficient.
2. **Document this as a future decision gate:** If session revocation becomes a requirement, a Redis session store on Railway ($5/month) handles up to 100,000 concurrent sessions.
3. **No action required now.**

### Residual Risk After Mitigation
Very Low. This is a theoretical future risk, not a current concern. Documented for completeness.

---

## R-010: Patient Search / Navigation Not Specified in PRD

**Severity:** P2  
**Likelihood:** High  
**Category:** Scope / UX Completeness  
**Source Decision:** PRD scope limited to "Patient header" and "Clinical cards" (PRD2_MODERNIZED.md)

### Description
The PRD specifies the patient dashboard for a known patient ID. It does not specify how a clinician arrives at a patient's dashboard — i.e., patient search, patient list, or recent patients. Without this, the dashboard is technically functional but unreachable without manually typing a patient ID in the URL.

### Trigger Condition
Deploy the dashboard without any patient navigation mechanism. Reviewer asks "How do I get to Patient 123's dashboard?"

### Impact
- Dashboard cannot be demonstrated to clients without manually constructing URLs.
- Feature parity claim is undermined if the PHP dashboard includes a patient search bar and the new one doesn't.

### Mitigation
1. **Minimal patient search bar on the landing page:** A simple `<input>` on `/dashboard` that calls `GET /apis/default/fhir/Patient?name=${searchTerm}`. This is not a full patient list — just a search-to-navigate mechanism. Implementation cost: ~30 minutes.
2. **Document this as a scope augmentation:** The ARCHITECTURE_PRD2_MODERNIZED.md clarifies that patient navigation is a necessary shell for demonstrating the dashboard, not a redesign of the patient search workflow.
3. **The original OpenEMR patient list page can still be used:** Clinicians can find the patient in the PHP interface and then switch to the new dashboard by constructing the URL. This is clunky but functional for V1.

### Residual Risk After Mitigation
Low. A minimal search bar resolves the usability gap. The full patient list with advanced filters is a roadmap item, not a V1 requirement.

---

# 3. Risk Summary by Severity

| Severity | Count | Risks                                                                 |
| -------- | ----- | --------------------------------------------------------------------- |
| **P0**   | 1     | R-001: OAuth2 token exposure via client-side JavaScript               |
| **P1**   | 4     | R-002: CORS proxy complexity, R-003: FHIR schema drift, R-007: ACR bypass, R-008: Platform outage |
| **P2**   | 4     | R-004: AI hallucinations, R-005: Stale data, R-006: Cold starts, R-010: Missing patient navigation |
| **P3**   | 1     | R-009: Session store scalability (future concern)                     |

---

# 4. Pre-Deployment Gate Criteria

The following risks must be **mitigated and verified** before the dashboard is demonstrated to remote clients:

- [ ] **R-001:** `auth.config.ts` reviewed — no `accessToken` in client session callback. **Security Audit Agent** has reviewed **auth-implementer** auth changes for R-001. Playwright test confirms no token in `window` object.
- [ ] **R-002:** FHIR proxy route tested against target OpenEMR instance; fallback error state renders correctly if proxy fails.
- [ ] **R-003:** At least one clinical card tested with Zod validation on a real patient record; mismatch logged, component does not crash.
- [ ] **R-004:** Side-by-side screenshot comparison completed for all six cards + lab results against PHP dashboard.
- [ ] **R-007:** `AUDIT_PRD2_MODERNIZED.md` committed with sign-off for each agent-generated artifact.
- [ ] **R-010:** Minimal patient search bar implemented and functional.

---

# 5. Risk Acceptance

The following risks are **accepted** for V1 deployment without full mitigation:

- **R-005 (Stale data):** The 30-second cache TTL is accepted as clinically reasonable for the data types in scope. The manual refresh button and refetch-on-focus are sufficient mitigations.
- **R-006 (Cold starts):** If deployed on Vercel without Pro tier, cold start delays are accepted as a V1 UX trade-off. Railway deployment eliminates this entirely.
- **R-009 (Session store):** Accepted. JWT strategy is sufficient for V1. Database sessions are a future concern.

---

# 6. Risk Monitoring Plan

| Risk | Monitoring Method                                      | Alert Trigger                                |
| ---- | ------------------------------------------------------ | -------------------------------------------- |
| R-001 | **Security Audit Agent** gate on **auth-implementer** PRs; Playwright security test in CI | Missing review or test fails → block merge |
| R-002 | Proxy error rate in structured logs                   | >5% of FHIR requests failing via proxy       |
| R-003 | Zod validation error rate in structured logs           | Any validation failure → investigate within 24h |
| R-004 | Manual visual review (not automatable)                | Reviewer flags hallucinated field            |
| R-005 | N/A (accepted risk)                                   | N/A                                          |
| R-006 | Vercel/Railway dashboard latency monitoring           | P95 latency >3s for initial load             |
| R-007 | ACR checklist completeness in `AUDIT.md`              | Missing sign-off → block deployment          |
| R-008 | Uptime monitor (e.g., Upptime, Better Stack)          | Frontend returns non-200 → alert maintainer  |
| R-010 | N/A (mitigated with search bar implementation)        | N/A                                          |

---

# 7. Document Governance

This risk register is a living document. It is updated:

- When a new architectural decision is made that introduces a new risk.
- When a risk materializes into an incident (post-mortem update).
- When a mitigation is implemented and the residual risk is re-evaluated.
- Before every deployment to a new environment (Dev → Staging → Production).

**Last reviewed:** 2026-05-07  
**Next review:** Before first deployment to staging

**Signed off by:** ___________________________ (Monica Peters, AI-First Engineer)

---

*This document is part of the Echelon Enterprise Engineering governance suite. It is traceable to ARCHITECTURE_PRD2_MODERNIZED.md §1-21 and serves as the primary input to the Agent Council Review process.*
```