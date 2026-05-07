/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Minimal Zod schemas for FHIR JSON validation at the trust boundary (architecture §5).
 *
 * Usage: Parse responses in hooks after `/api/fhir` returns JSON.
 *
 * Security/PHI: Validates shape before rendering; do not log parse errors with raw payloads in production.
 * HIPAA: Treat validation failures as “data unavailable,” not silent render of untrusted shapes.
 * FHIR: R4-style JSON (loose `resource` objects on entries).
 * Accessibility: N/A.
 * Performance: Lightweight object checks only.
 * Stability: Invalid payloads return typed `success: false` results for UI error states.
 * Legal/compliance: N/A.
 */

import { z } from "zod";

const fhirBundleSchema = z.object({
  resourceType: z.literal("Bundle"),
  type: z.string().optional(),
  total: z.number().optional(),
  entry: z
    .array(
      z.object({
        resource: z.record(z.string(), z.unknown()).optional(),
      }),
    )
    .optional(),
});

const fhirPatientSchema = z.object({
  resourceType: z.literal("Patient"),
  id: z.string().optional(),
  active: z.boolean().optional(),
  name: z
    .array(
      z.object({
        text: z.string().optional(),
        family: z.string().optional(),
        given: z.array(z.string()).optional(),
      }),
    )
    .optional(),
  birthDate: z.string().optional(),
  gender: z.string().optional(),
  identifier: z
    .array(
      z.object({
        system: z.string().optional(),
        value: z.string().optional(),
      }),
    )
    .optional(),
});

const fhirOperationOutcomeSchema = z.object({
  resourceType: z.literal("OperationOutcome"),
});

export type FhirBundle = z.infer<typeof fhirBundleSchema>;
export type FhirPatient = z.infer<typeof fhirPatientSchema>;

export function parseBundle(json: unknown):
  | { success: true; data: FhirBundle }
  | { success: false } {
  const parsed = fhirBundleSchema.safeParse(json);
  if (!parsed.success) {
    return { success: false };
  }
  return { success: true, data: parsed.data };
}

export function parsePatient(json: unknown):
  | { success: true; data: FhirPatient }
  | { success: false } {
  const parsed = fhirPatientSchema.safeParse(json);
  if (!parsed.success) {
    return { success: false };
  }
  return { success: true, data: parsed.data };
}

export function isOperationOutcome(json: unknown): boolean {
  return fhirOperationOutcomeSchema.safeParse(json).success;
}
