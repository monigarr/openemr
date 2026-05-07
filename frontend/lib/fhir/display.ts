/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Format FHIR Patient fields for the persistent header (name, DOB, sex, MRN, active status).
 *
 * Usage: `formatPatientBanner(patient)` from dashboard layout components.
 *
 * Security/PHI: Display-only helpers; callers must not log formatted PHI to the browser console.
 * HIPAA: Minimum necessary presentation strings.
 * FHIR: Patient (R4 subset).
 * Accessibility: Returns plain strings suitable for text content.
 * Performance: O(n) over identifiers / name parts.
 * Stability: Graceful fallbacks when fields are missing.
 * Legal/compliance: N/A.
 */

import type { FhirPatient } from "@/lib/fhir/schemas";

export function formatPatientName(patient: FhirPatient): string {
  const text = patient.name?.[0]?.text;
  if (text) {
    return text;
  }
  const family = patient.name?.[0]?.family;
  const given = patient.name?.[0]?.given?.join(" ");
  const parts = [given, family].filter(Boolean);
  return parts.length > 0 ? parts.join(" ") : "—";
}

export function formatMrn(patient: FhirPatient): string {
  const ids = patient.identifier ?? [];
  const preferred =
    ids.find((i) => i.system?.includes("MRN")) ??
    ids.find((i) => (i.value ?? "").length > 0) ??
    ids[0];
  return preferred?.value ?? patient.id ?? "—";
}

export function formatActive(patient: FhirPatient): string {
  if (patient.active === undefined) {
    return "—";
  }
  return patient.active ? "Active" : "Inactive";
}
