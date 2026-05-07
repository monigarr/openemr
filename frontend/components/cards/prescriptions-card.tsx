/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Prescriptions card (MedicationRequest with intent=order).
 *
 * Usage: `<PrescriptionsCard patientId={id} />`
 *
 * Security/PHI: Prescription data is PHI.
 * HIPAA: Minimum necessary display.
 * FHIR: MedicationRequest (intent=order).
 * Accessibility: Semantic list.
 * Performance: One query.
 * Stability: Isolated failure modes.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { usePrescriptions } from "@/hooks/use-prescriptions";
import { displayFromUnknownResource, statusLabel } from "@/lib/fhir/resource-text";

export function PrescriptionsCard({ patientId }: { patientId: string }) {
  const query = usePrescriptions(patientId);

  return (
    <ClinicalCard
      title="Prescriptions"
      emptyDescription="No active prescription orders are on file."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Prescriptions</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {(bundle.entry ?? []).map((entry, idx) => {
                const resource = entry.resource as Record<string, unknown> | undefined;
                const key = typeof resource?.id === "string" ? resource.id : `rx-${idx}`;
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
