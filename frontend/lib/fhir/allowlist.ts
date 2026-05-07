/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: SSRF mitigation for the FHIR proxy — only allowlisted resource roots and safe path segments.
 *
 * Usage: Imported by `app/api/fhir/[...path]/route.ts`.
 *
 * Security/PHI: Blocks unexpected resource types and path traversal patterns before server-side fetch to OpenEMR.
 * HIPAA: Defense-in-depth for minimum-necessary API calls.
 * FHIR: Allowlist matches dashboard read surface (Patient + clinical queries).
 * Accessibility: N/A.
 * Performance: O(n) over path segments.
 * Stability: Returns 403 for invalid paths; never forwards to OpenEMR.
 * Legal/compliance: N/A.
 */

const ALLOWED_RESOURCE_TYPES = new Set([
  "Patient",
  "AllergyIntolerance",
  "Condition",
  "MedicationRequest",
  "CareTeam",
  "Observation",
  "metadata",
]);

const SAFE_SEGMENT = /^[A-Za-z0-9\-.]+$/;

export function assertSafeFhirPath(path: string[]): void {
  if (path.length === 0) {
    throw new Error("Empty FHIR path");
  }

  const root = path[0] ?? "";
  if (!ALLOWED_RESOURCE_TYPES.has(root)) {
    throw new Error("FHIR resource type not allowed");
  }

  for (const segment of path) {
    if (segment.includes("..") || segment.includes("//")) {
      throw new Error("Unsafe FHIR path segment");
    }
    if (!SAFE_SEGMENT.test(segment)) {
      throw new Error("Unsafe FHIR path segment");
    }
  }
}
