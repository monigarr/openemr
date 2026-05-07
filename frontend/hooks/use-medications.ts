/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Active MedicationRequest list (medications card).
 *
 * Usage: `useMedications(patientId)`
 *
 * Security/PHI: Medications are PHI.
 * HIPAA: N/A.
 * FHIR: `GET MedicationRequest?patient=…&status=active`
 * Accessibility: N/A.
 * Performance: Bundle parse.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function useMedications(patientId: string) {
  const path = `MedicationRequest?patient=${encodeURIComponent(patientId)}&status=active`;
  return useFhirGet(["fhir", "MedicationRequest", "active", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid MedicationRequest bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
