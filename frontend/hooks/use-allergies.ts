/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: AllergyIntolerance search for a patient (clinical card).
 *
 * Usage: `useAllergies(patientId)`
 *
 * Security/PHI: Allergies are PHI.
 * HIPAA: N/A.
 * FHIR: `GET AllergyIntolerance?patient=…`
 * Accessibility: N/A.
 * Performance: Bundle parse O(n) over entries.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function useAllergies(patientId: string) {
  const path = `AllergyIntolerance?patient=${encodeURIComponent(patientId)}`;
  return useFhirGet(["fhir", "AllergyIntolerance", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid AllergyIntolerance bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
