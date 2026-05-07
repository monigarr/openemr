# Patient dashboard modernization — framework defense (PRD2 Modernized)

**Version:** 0.1.0  
**Date:** 2026-05-07  
**Author:** Monica Peters <monica.peters@gfachallenger.gauntletai.com>  
**Status:** Active — living document for the Next.js presentation-layer port

## Executive summary

This initiative **reimplements the OpenEMR patient dashboard presentation layer** as a **Next.js (App Router)** application located at [`frontend/`](../frontend/) while **consuming OpenEMR’s existing OAuth2/OIDC + FHIR APIs**. The PHP monolith remains the system of record; this work intentionally avoids new server-side business logic in OpenEMR core for dashboard-specific needs.

Primary references:

- Requirements: [`Documentation/PRD2_MODERNIZED.md`](PRD2_MODERNIZED.md)
- Architecture: [`Documentation/ARCHITECTURE_PRD2_MODERNIZED.md`](ARCHITECTURE_PRD2_MODERNIZED.md)
- Risks: [`Documentation/ARCHITECTURE_RISKS_PRD2_MODERNIZED.md`](ARCHITECTURE_RISKS_PRD2_MODERNIZED.md)

## Framework choice: Next.js + React

### Why Next.js

- **Operational fit:** OpenEMR deployments already expect HTTPS, institutional SSO patterns, and audited API access. Next.js provides a mainstream, well-supported model for **OIDC login** and **server-mediated API calls** that keeps secrets off the browser JavaScript surface.
- **Security posture:** Auth.js (NextAuth v5) supports **encrypted HTTP-only session cookies** and a clean separation between **public client code** and **server-only token usage**, which directly mitigates token exposure risks (see risk **R-001** in the risk register).
- **FHIR integration ergonomics:** The dashboard is fundamentally a **composition of networked clinical queries** with independent failure modes. React + TanStack Query provides **per-card isolation** (loading/error/empty), aligning with the product requirement that one failing section must not blank the entire dashboard.
- **Delivery speed without sacrificing governance:** The stack matches the architecture decision (**ADR-001**) and is well-supported in modern tooling ecosystems (CI, static analysis, component libraries).

### What we gain versus PHP server-rendered UI

- **Independent client fetch orchestration:** Cards can load asynchronously with explicit UX states instead of full-page reload semantics.
- **Stronger client/server boundary for secrets:** Bearer tokens are attached **only** in server route handlers (`/api/fhir/*`) reading the Auth.js JWT cookie, not from `"use client"` trees.
- **Testability:** We can ship **Playwright smoke tests** around routing and unauthenticated API boundaries immediately, and expand to full authenticated flows when CI has a deterministic OpenEMR profile.

### Tradeoffs (explicit)

- **Two runtime surfaces:** Operators must understand both OpenEMR (PHP) and the Next deployment (Node). This increases operational literacy requirements versus a single monolith UI.
- **CORS / proxy complexity:** Browsers may require a **FHIR proxy** (`/api/fhir/[...path]`) and strict allowlisting to avoid SSRF and token leakage; this is additional code to review and monitor (**R-002**).
- **Session model differences:** OIDC refresh and session expiry must be handled carefully to avoid “silent partial outages” where the UI loads but FHIR calls begin failing until re-auth.

## Local development (high level)

1. Configure OpenEMR OAuth2/OIDC client + FHIR per your environment.
2. Copy [`frontend/.env.example`](../frontend/.env.example) → `frontend/.env.local`.
3. Run the app from `frontend/` (`npm run dev`) and complete login via OpenEMR.

## Verification notes

- **Playwright:** `frontend/e2e/*` includes smoke coverage for unauthenticated redirects and the FHIR proxy **401** boundary, with placeholders for full authenticated clinical scenarios pending CI seed data.

## Change control

Material changes to auth, proxy allowlists, or clinical sections must be reflected in:

- [`Documentation/AUDIT_PRD2_MODERNIZED.md`](AUDIT_PRD2_MODERNIZED.md)
- [`Documentation/ARCHITECTURE_RISKS_PRD2_MODERNIZED.md`](ARCHITECTURE_RISKS_PRD2_MODERNIZED.md) (when risk posture changes)
