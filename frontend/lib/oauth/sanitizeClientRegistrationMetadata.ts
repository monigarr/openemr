/**
 * @version 0.1.0
 * @date 2026-05-09
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Normalize OAuth 2.0 Dynamic Client Registration metadata before POSTing to stock OpenEMR (unmodified `upstream/master`). Empty `jwks` / `jwks_uri` values must be omitted — not sent as `""` — so Symfony request parsing and `jwks` handling behave reliably.
 *
 * Usage: Call `sanitizeClientRegistrationMetadataForLegacyOpenEmr` on the object you pass to `JSON.stringify` for the registration endpoint. Use from server-side Next code or tooling only; do not embed client secrets in browser bundles.
 *
 * Example:
 *   const body = sanitizeClientRegistrationMetadataForLegacyOpenEmr({ ...metadata });
 *   await fetch(registrationUrl, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) });
 *
 * Dependencies: None (pure TypeScript).
 *
 * Security/PHI: Registration payloads may describe the app, not patient data; still avoid logging full bodies in production.
 * HIPAA: N/A for this helper alone — depends on caller context.
 * FHIR: N/A — OAuth client metadata, not FHIR resources.
 * Accessibility: N/A — non-UI.
 * Performance: O(1) shallow copy; safe for typical registration objects.
 * Stability: Idempotent for already-sanitized input; does not validate JWKS structure beyond emptiness checks.
 * Legal/compliance: N/A.
 */

/**
 * Returns a shallow copy of `metadata` with empty optional JWKS fields removed.
 * Inline `jwks` must be a parsed JWK Set object when provided; do not pass a non-empty invalid JSON string here — validate before calling.
 */
export function sanitizeClientRegistrationMetadataForLegacyOpenEmr(
  metadata: Record<string, unknown>
): Record<string, unknown> {
  const out: Record<string, unknown> = { ...metadata };

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
  if (
    jwks === "" ||
    (typeof jwks === "string" && jwks.trim() === "")
  ) {
    delete out.jwks;
  }

  return out;
}
