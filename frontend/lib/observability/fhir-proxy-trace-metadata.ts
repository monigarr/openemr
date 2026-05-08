/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Build Langfuse-safe metadata for FHIR proxy spans (resource type only; no logical ids, query strings, or bodies).
 *
 * Usage: Called from `app/api/fhir/[...path]/route.ts` when observability is active.
 *
 * Security/PHI: Never include path segments after resource type or search params (may encode patient identifiers).
 * HIPAA: Minimum necessary metadata only.
 * FHIR: N/A
 * Accessibility: N/A
 * Performance: Trivial.
 * Stability: Defensive when path empty.
 * Legal/compliance: N/A
 */

export function fhirProxyTraceMetadata(path: string[]): Record<string, string> {
  const resourceType = path.length > 0 ? path[0]! : "unknown";
  return {
    track: "prd2_modernized",
    surface: "fhir_proxy",
    "fhir.resource_type": resourceType,
    "fhir.operation": "read",
  };
}
