/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Persistent patient identity bar (name, DOB, sex, MRN, active status) for wrong-patient prevention.
 *
 * Usage: Render inside `DashboardShell` for `/dashboard/patient/[id]`.
 *
 * Security/PHI: Displays demographics; do not log to console.
 * HIPAA: Minimum necessary header fields only.
 * FHIR: Patient read model.
 * Accessibility: Use plain text; banner remains visible while scrolling (`sticky`).
 * Performance: One Patient query shared conceptually with page context.
 * Stability: Distinct loading/error fallbacks so the shell remains usable.
 * Legal/compliance: N/A.
 */

"use client";

import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { usePatient } from "@/hooks/use-patient";
import { formatActive, formatMrn, formatPatientName } from "@/lib/fhir/display";

export function PatientBanner({ patientId }: { patientId: string }) {
  const query = usePatient(patientId);

  if (query.isPending) {
    return (
      <div aria-busy="true" className="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="mx-auto max-w-6xl px-4 py-3">
          <div className="flex flex-wrap items-center gap-3">
            <Skeleton className="h-6 w-64" />
            <Skeleton className="h-6 w-40" />
            <Skeleton className="h-6 w-28" />
          </div>
          <span className="sr-only">Loading patient header</span>
        </div>
      </div>
    );
  }

  if (query.isError || !query.data) {
    return (
      <div className="sticky top-0 z-20 border-b border-rose-200 bg-rose-50">
        <div className="mx-auto max-w-6xl px-4 py-3 text-sm text-rose-900">
          Patient header unavailable. You can retry loading the dashboard sections below.
          <button
            type="button"
            className="ml-3 underline"
            onClick={() => query.refetch()}
          >
            Retry header
          </button>
        </div>
      </div>
    );
  }

  const patient = query.data;
  const name = formatPatientName(patient);
  const mrn = formatMrn(patient);
  const active = formatActive(patient);
  const dob = patient.birthDate ?? "—";
  const sex = patient.gender ?? "—";

  const badgeVariant = patient.active === false ? "danger" : "success";

  return (
    <div className="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
      <div className="mx-auto max-w-6xl px-4 py-3">
        <Card className="border-slate-200 bg-slate-50">
          <div className="flex flex-col gap-2 p-4 md:flex-row md:items-center md:justify-between">
            <div className="space-y-1">
              <div className="text-lg font-semibold text-slate-900">{name}</div>
              <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-700">
                <span>
                  <span className="font-medium text-slate-900">DOB:</span> {dob}
                </span>
                <span>
                  <span className="font-medium text-slate-900">Sex:</span> {sex}
                </span>
                <span>
                  <span className="font-medium text-slate-900">MRN:</span> {mrn}
                </span>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-slate-600">Status</span>
              <Badge variant={badgeVariant}>{active}</Badge>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}
