/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Laboratory Observations for the “additional section” (lab results).
 *
 * Usage: `useLabResults(patientId)`
 *
 * Security/PHI: Lab results are PHI.
 * HIPAA: N/A.
 * FHIR: `GET Observation?patient=…&category=laboratory`
 * Accessibility: N/A.
 * Performance: Bundle parse; consider pagination in future.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function useLabResults(patientId: string) {
  const path = `Observation?patient=${encodeURIComponent(patientId)}&category=laboratory`;
  return useFhirGet(["fhir", "Observation", "lab", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid Observation bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
