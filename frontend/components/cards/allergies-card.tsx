/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Allergies clinical card (FHIR AllergyIntolerance search).
 *
 * Usage: `<AllergiesCard patientId={id} />`
 *
 * Security/PHI: Renders allergy-related PHI from FHIR.
 * HIPAA: Minimum necessary list display.
 * FHIR: AllergyIntolerance (R4 JSON).
 * Accessibility: Semantic list.
 * Performance: One query.
 * Stability: Isolated error/empty states via `ClinicalCard`.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useAllergies } from "@/hooks/use-allergies";
import { displayFromUnknownResource, statusLabel } from "@/lib/fhir/resource-text";

export function AllergiesCard({ patientId }: { patientId: string }) {
  const query = useAllergies(patientId);

  return (
    <ClinicalCard
      title="Allergies"
      emptyDescription="No allergies on file for this patient."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Allergies</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {(bundle.entry ?? []).map((entry, idx) => {
                const resource = entry.resource as Record<string, unknown> | undefined;
                const key = typeof resource?.id === "string" ? resource.id : `allergy-${idx}`;
                const text = displayFromUnknownResource(resource);
                const status = statusLabel(resource);
                return (
                  <li key={key} className="text-sm text-slate-800">
                    <span className="font-medium">{text}</span>
                    {status ? <span className="text-slate-500"> — {status}</span> : null}
                  </li>
                );
              })}
            </ul>
          </CardContent>
        </Card>
      )}
    </ClinicalCard>
  );
}
