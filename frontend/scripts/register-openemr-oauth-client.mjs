#!/usr/bin/env node
/**
 * POST Dynamic Client Registration to stock OpenEMR without sending empty jwks / jwks_uri strings.
 * Mirrors frontend/lib/oauth/sanitizeClientRegistrationMetadata.ts (keep logic in sync).
 *
 * Usage:
 *   OPENEMR_REGISTRATION_URL="https://localhost:9300/oauth2/default/registration" \
 *   node scripts/register-openemr-oauth-client.mjs scripts/registration.example.json
 *
 * Optional: NODE_TLS_REJECT_UNAUTHORIZED=0 for local self-signed OpenEMR (dev only).
 */

import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * @param {Record<string, unknown>} metadata
 * @returns {Record<string, unknown>}
 */
function sanitizeClientRegistrationMetadataForLegacyOpenEmr(metadata) {
  const out = { ...metadata };
  const jwksUri = out.jwks_uri;
  if (
    jwksUri === undefined ||
    jwksUri === null ||
    jwksUri === "" ||
    (typeof jwksUri === "string" && jwksUri.trim() === "")
  ) {
    delete out.jwks_uri;
  }
  const jwks = out.jwks;
  if (jwks === "" || (typeof jwks === "string" && jwks.trim() === "")) {
    delete out.jwks;
  }
  return out;
}

const jsonPath = process.argv[2];
if (!jsonPath) {
  console.error(
    "Usage: node scripts/register-openemr-oauth-client.mjs <registration-body.json>",
  );
  process.exit(1);
}

const url = process.env.OPENEMR_REGISTRATION_URL;
if (!url) {
  console.error("Set OPENEMR_REGISTRATION_URL to your /oauth2/.../registration endpoint.");
  process.exit(1);
}

const raw = readFileSync(resolve(process.cwd(), jsonPath), "utf8");
/** @type {Record<string, unknown>} */
const body = JSON.parse(raw);
const payload = sanitizeClientRegistrationMetadataForLegacyOpenEmr(body);

const res = await fetch(url, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(payload),
});

const text = await res.text();
let parsed;
try {
  parsed = JSON.parse(text);
} catch {
  console.error(res.status, text);
  process.exit(1);
}

if (!res.ok) {
  console.error(res.status, parsed);
  process.exit(1);
}

console.log(JSON.stringify(parsed, null, 2));
