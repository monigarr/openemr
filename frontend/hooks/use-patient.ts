/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Fetch and validate a FHIR Patient for the dashboard header context.
 *
 * Usage: `usePatient(patientId)` in client components.
 *
 * Security/PHI: Patient demographics are PHI — avoid console logging.
 * HIPAA: Minimum necessary: only fields needed for banner/cards.
 * FHIR: `GET /Patient/{id}`
 * Accessibility: N/A.
 * Performance: Single resource fetch.
 * Stability: Returns `null` data on validation failure (treated as error state by UI).
 * Legal/compliance: N/A.
 */

"use client";

import { useFhirGet } from "@/hooks/use-fhir-get";
import { parsePatient, type FhirPatient } from "@/lib/fhir/schemas";

export function usePatient(patientId: string) {
  return useFhirGet(["fhir", "Patient", patientId], `Patient/${encodeURIComponent(patientId)}`, (json) => {
    const parsed = parsePatient(json);
    if (!parsed.success) {
      throw new Error("Invalid Patient response");
    }
    return parsed.data;
  });
}

export type { FhirPatient };
