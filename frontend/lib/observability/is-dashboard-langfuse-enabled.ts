/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Opt-in gate for Track B Langfuse export (mirrors Track A “off by default” posture; OpenEMR Portal global does not apply to the Node app).
 *
 * Usage: Set `DASHBOARD_LANGFUSE_ENABLE=true` plus `LANGFUSE_PUBLIC_KEY`, `LANGFUSE_SECRET_KEY`, and optionally `LANGFUSE_BASE_URL` on the Next.js server environment.
 *
 * Security/PHI: Enabling export is an operator choice; traces must remain metadata-first (no FHIR bodies or patient identifiers).
 * HIPAA: N/A — no PHI in this module by itself.
 * FHIR: N/A
 * Accessibility: N/A
 * Performance: N/A
 * Stability: Pure env read.
 * Legal/compliance: N/A
 */

function isTruthyEnv(value: string | undefined): boolean {
  if (value === undefined) {
    return false;
  }
  const normalized = value.trim().toLowerCase();
  return normalized === "1" || normalized === "true" || normalized === "yes";
}

export function isDashboardLangfuseEnabled(): boolean {
  if (!isTruthyEnv(process.env.DASHBOARD_LANGFUSE_ENABLE)) {
    return false;
  }
  const publicKey = process.env.LANGFUSE_PUBLIC_KEY?.trim();
  const secretKey = process.env.LANGFUSE_SECRET_KEY?.trim();
  return Boolean(publicKey && secretKey);
}
