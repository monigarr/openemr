/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Active medications card (MedicationRequest search).
 *
 * Usage: `<MedicationsCard patientId={id} />`
 *
 * Security/PHI: Medication data is PHI.
 * HIPAA: Minimum necessary display.
 * FHIR: MedicationRequest (status=active).
 * Accessibility: Semantic list.
 * Performance: One query.
 * Stability: Isolated failure modes.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useMedications } from "@/hooks/use-medications";
import { displayFromUnknownResource, intentLabel, statusLabel } from "@/lib/fhir/resource-text";

export function MedicationsCard({ patientId }: { patientId: string }) {
  const query = useMedications(patientId);

  return (
    <ClinicalCard
      title="Medications"
      emptyDescription="No active medication requests are on file."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Medications</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {(bundle.entry ?? []).map((entry, idx) => {
                const resource = entry.resource as Record<string, unknown> | undefined;
                const key = typeof resource?.id === "string" ? resource.id : `med-${idx}`;
                const text = displayFromUnknownResource(resource);
                const status = statusLabel(resource);
                const intent = intentLabel(resource);
                return (
                  <li key={key} className="text-sm text-slate-800">
                    <span className="font-medium">{text}</span>
                    <span className="text-slate-500">
                      {intent ? ` — ${intent}` : ""}
                      {status ? ` — ${status}` : ""}
                    </span>
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
