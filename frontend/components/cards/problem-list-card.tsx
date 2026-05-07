/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Problem list card (active Condition resources).
 *
 * Usage: `<ProblemListCard patientId={id} />`
 *
 * Security/PHI: Problem list entries are PHI.
 * HIPAA: Minimum necessary display.
 * FHIR: Condition (active clinical-status).
 * Accessibility: Semantic list.
 * Performance: One query.
 * Stability: Isolated failure modes.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useProblemList } from "@/hooks/use-problem-list";
import { displayFromUnknownResource, statusLabel } from "@/lib/fhir/resource-text";

export function ProblemListCard({ patientId }: { patientId: string }) {
  const query = useProblemList(patientId);

  return (
    <ClinicalCard
      title="Problem List"
      emptyDescription="No active problems are recorded for this patient."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Problem List</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {(bundle.entry ?? []).map((entry, idx) => {
                const resource = entry.resource as Record<string, unknown> | undefined;
                const key = typeof resource?.id === "string" ? resource.id : `condition-${idx}`;
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
