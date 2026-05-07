/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Care Team card (CareTeam search).
 *
 * Usage: `<CareTeamCard patientId={id} />`
 *
 * Security/PHI: Care team data can be sensitive.
 * HIPAA: Minimum necessary display.
 * FHIR: CareTeam (status=active).
 * Accessibility: Semantic list.
 * Performance: One query.
 * Stability: Isolated failure modes.
 * Legal/compliance: N/A.
 */

"use client";

import { ClinicalCard } from "@/components/cards/clinical-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCareTeam } from "@/hooks/use-care-team";
import { statusLabel } from "@/lib/fhir/resource-text";

function careTeamTitle(resource: Record<string, unknown> | undefined): string {
  const name = resource?.name;
  if (typeof name === "string" && name.length > 0) {
    return name;
  }
  const id = typeof resource?.id === "string" ? resource.id : undefined;
  return id ? `CareTeam/${id}` : "Care Team";
}

function participantLines(resource: Record<string, unknown> | undefined): string[] {
  const participants = resource?.participant as
    | Array<{
        member?: { display?: string; reference?: string };
        role?: Array<{ text?: string; concept?: { text?: string } }>;
      }>
    | undefined;

  if (!participants || participants.length === 0) {
    return [];
  }

  return participants.map((p) => {
    const who = p.member?.display ?? p.member?.reference ?? "Participant";
    const roleText = p.role?.[0]?.text ?? p.role?.[0]?.concept?.text;
    return roleText ? `${who} — ${roleText}` : who;
  });
}

export function CareTeamCard({ patientId }: { patientId: string }) {
  const query = useCareTeam(patientId);

  return (
    <ClinicalCard
      title="Care Team"
      emptyDescription="No active care team is recorded for this patient."
      query={query}
    >
      {(bundle) => (
        <Card>
          <CardHeader>
            <CardTitle>Care Team</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {(bundle.entry ?? []).map((entry, idx) => {
              const resource = entry.resource as Record<string, unknown> | undefined;
              const key = typeof resource?.id === "string" ? resource.id : `team-${idx}`;
              const title = careTeamTitle(resource);
              const status = statusLabel(resource);
              const lines = participantLines(resource);

              return (
                <div key={key} className="space-y-2">
                  <div className="text-sm font-semibold text-slate-900">
                    {title}
                    {status ? <span className="font-normal text-slate-500"> — {status}</span> : null}
                  </div>
                  {lines.length > 0 ? (
                    <ul className="space-y-1">
                      {lines.map((line, pIdx) => (
                        <li key={`${key}-p-${pIdx}`} className="text-sm text-slate-800">
                          {line}
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-sm text-slate-600">No participants listed on this care team.</p>
                  )}
                </div>
              );
            })}
          </CardContent>
        </Card>
      )}
    </ClinicalCard>
  );
}
