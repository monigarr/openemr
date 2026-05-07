/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Best-effort human-readable strings from loosely typed FHIR JSON for dashboard lists.
 *
 * Usage: Used by card components; never used for clinical decision logic.
 *
 * Security/PHI: Input is PHI once rendered — do not log raw resources client-side.
 * HIPAA: Display-only.
 * FHIR: R4 JSON patterns (codeable concepts, references).
 * Accessibility: Plain text suitable for list items.
 * Performance: O(1) per resource.
 * Stability: Always returns a non-empty fallback.
 * Legal/compliance: N/A.
 */

export function displayFromUnknownResource(resource: Record<string, unknown> | undefined): string {
  if (!resource) {
    return "—";
  }

  const resourceType = typeof resource.resourceType === "string" ? resource.resourceType : "Resource";

  const code = resource.code as { text?: string; coding?: Array<{ display?: string }> } | undefined;
  if (code?.text) {
    return code.text;
  }
  const codingDisplay = code?.coding?.find((c) => c.display)?.display;
  if (codingDisplay) {
    return codingDisplay;
  }

  const medConcept = resource.medicationCodeableConcept as
    | { text?: string; coding?: Array<{ display?: string }> }
    | undefined;
  if (medConcept?.text) {
    return medConcept.text;
  }
  const medCoding = medConcept?.coding?.find((c) => c.display)?.display;
  if (medCoding) {
    return medCoding;
  }

  const medRef = resource.medicationReference as { display?: string; reference?: string } | undefined;
  if (medRef?.display) {
    return medRef.display;
  }
  if (medRef?.reference) {
    return medRef.reference;
  }

  const subject = resource.subject as { display?: string } | undefined;
  if (subject?.display) {
    return subject.display;
  }

  const id = typeof resource.id === "string" ? resource.id : undefined;
  return id ? `${resourceType}/${id}` : resourceType;
}

export function intentLabel(resource: Record<string, unknown> | undefined): string | undefined {
  const intent = resource?.intent;
  return typeof intent === "string" ? intent : undefined;
}

export function statusLabel(resource: Record<string, unknown> | undefined): string | undefined {
  const status = resource?.status;
  return typeof status === "string" ? status : undefined;
}

export function observationLine(resource: Record<string, unknown> | undefined): string {
  if (!resource) {
    return "—";
  }

  const code = resource.code as { text?: string } | undefined;
  const codeText = code?.text ?? "Observation";

  const vq = resource.valueQuantity as { value?: number; unit?: string } | undefined;
  if (vq?.value !== undefined) {
    const unit = vq.unit ? ` ${vq.unit}` : "";
    return `${codeText}: ${vq.value}${unit}`;
  }

  const vs = resource.valueString;
  if (typeof vs === "string" && vs.length > 0) {
    return `${codeText}: ${vs}`;
  }

  const effective = resource.effectiveDateTime;
  if (typeof effective === "string") {
    return `${codeText} (${effective})`;
  }

  return codeText;
}
