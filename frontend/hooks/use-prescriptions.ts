/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: MedicationRequest with intent=order (prescriptions card), distinct query from active medications per architecture §19.4.
 *
 * Usage: `usePrescriptions(patientId)`
 *
 * Security/PHI: Prescriptions are PHI.
 * HIPAA: N/A.
 * FHIR: `GET MedicationRequest?patient=…&status=active&intent=order`
 * Accessibility: N/A.
 * Performance: Bundle parse.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function usePrescriptions(patientId: string) {
  const path = `MedicationRequest?patient=${encodeURIComponent(patientId)}&status=active&intent=order`;
  return useFhirGet(["fhir", "MedicationRequest", "orders", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid MedicationRequest (orders) bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
