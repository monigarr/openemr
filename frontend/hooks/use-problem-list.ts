/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Active Condition list (problem list) for a patient.
 *
 * Usage: `useProblemList(patientId)`
 *
 * Security/PHI: Conditions are PHI.
 * HIPAA: N/A.
 * FHIR: `GET Condition?patient=…&clinical-status=active`
 * Accessibility: N/A.
 * Performance: Bundle parse.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function useProblemList(patientId: string) {
  const path = `Condition?patient=${encodeURIComponent(patientId)}&clinical-status=active`;
  return useFhirGet(["fhir", "Condition", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid Condition bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
