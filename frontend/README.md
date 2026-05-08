# PRD2 Modernized — Patient Dashboard (Next.js)

OpenEMR **presentation-layer** port: OAuth2/OIDC + FHIR via [`Documentation/PRD2_MODERNIZED.md`](../Documentation/PRD2_MODERNIZED.md).

## Prerequisites

1. **OpenEMR** running with REST + FHIR enabled (e.g. `docker/development-easy` — app on **https://localhost:9300**).
2. **OAuth client** registered in OpenEMR with redirect URI **`http://localhost:3000/api/auth/callback/openemr`** (same scheme/host/port as this app). Requires ACL **Administration → Super** for **API Clients**.
3. Node **24+** (see repo root `package.json` engines).

## Configure

```bash
cd frontend
cp .env.example .env.local
```

Edit **`.env.local`**:

| Variable | Example |
|----------|---------|
| `AUTH_SECRET` | 32+ byte random hex (single line) |
| `NEXTAUTH_URL` / `AUTH_URL` | `http://localhost:3000` |
| `AUTH_OPENEMR_ID` / `AUTH_OPENEMR_SECRET` | From OpenEMR app registration |
| `OPENEMR_OAUTH2_ISSUER` | `https://localhost:9300/oauth2/default` |
| `OPENEMR_BASE_URL` | `https://localhost:9300` |

**Self-signed OpenEMR TLS (local only):** add `NODE_TLS_REJECT_UNAUTHORIZED=0` to `.env.local` so Node can call `https://localhost:9300` for OIDC discovery and the FHIR proxy. **Do not use in production.**

## Run

```bash
npm install
npm run dev
```

Open **http://localhost:3000** (use **http**, not https, unless you terminate TLS in front of Next).

## Common errors

| Symptom | Fix |
|---------|-----|
| `error=Configuration` (Auth.js) | Fix `AUTH_SECRET` (one line), set real `AUTH_OPENEMR_*`, ensure `OPENEMR_OAUTH2_ISSUER` is reachable from Node; for self-signed dev TLS see above. |
| `jwks` / `invalid_client_metadata` on registration | Do not send empty `jwks` string; omit `jwks` in curl body or use valid `{"keys":[]}` if UI requires it — see prior notes. |
| Redirect URI mismatch | Must exactly match `NEXTAUTH_URL` + `/api/auth/callback/openemr`. |

## Tests

```bash
npm run test:e2e
```

Requires Playwright browsers: `npx playwright install chromium`.

## More

- Security: [`.cursor/rules/PRD2-Modernized-Frontend-Security.mdc`](../.cursor/rules/PRD2-Modernized-Frontend-Security.mdc)
- Architecture: [`Documentation/ARCHITECTURE_PRD2_MODERNIZED.md`](../Documentation/ARCHITECTURE_PRD2_MODERNIZED.md)
