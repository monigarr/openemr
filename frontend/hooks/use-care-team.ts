/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Active CareTeam search for a patient.
 *
 * Usage: `useCareTeam(patientId)`
 *
 * Security/PHI: Care team membership can be PHI/sensitive.
 * HIPAA: N/A.
 * FHIR: `GET CareTeam?patient=…&status=active`
 * Accessibility: N/A.
 * Performance: Bundle parse.
 * Stability: Invalid bundle → error state.
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parseBundle, type FhirBundle } from "@/lib/fhir/schemas";

export function useCareTeam(patientId: string) {
  const path = `CareTeam?patient=${encodeURIComponent(patientId)}&status=active`;
  return useFhirGet(["fhir", "CareTeam", patientId], path, (json) => {
    const parsed = parseBundle(json);
    if (!parsed.success) {
      throw new Error("Invalid CareTeam bundle");
    }
    return parsed.data;
  });
}

export type { FhirBundle };
