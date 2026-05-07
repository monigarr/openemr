/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Additional dashboard section — laboratory results (Observation, category=laboratory).
 *
 * Usage: `<LabResultsCard patientId={id} />`
 *
 * Security/PHI: Lab results are PHI.
 * HIPAA: Minimum necessary display.
 * FHIR: Observation (laboratory).
 * Accessibility: Semantic list.
 * Performance: One query; pagination not implemented.
 * Stability: Isolated failure modes.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useLabResults } from "@/hooks/use-lab-results";
import { observationLine } from "@/lib/fhir/resource-text";

export function LabResultsCard({ patientId }: { patientId: string }) {
  const query = useLabResults(patientId);

  return (
    <ClinicalCard
      title="Lab Results"
      emptyDescription="No laboratory observations are on file for this patient."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Lab Results</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {(bundle.entry ?? []).map((entry, idx) => {
                const resource = entry.resource as Record<string, unknown> | undefined;
                const key = typeof resource?.id === "string" ? resource.id : `lab-${idx}`;
                return (
                  <li key={key} className="text-sm text-slate-800">
                    {observationLine(resource)}
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
