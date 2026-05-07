/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Client composition of clinical cards for a patient dashboard route.
 *
 * Usage: Rendered from `app/dashboard/patient/[id]/page.tsx`.
 *
 * Security/PHI: Composes PHI-bearing cards; relies on `/api/fhir` cookie session.
 * HIPAA: Minimum necessary layout (grid of sections).
 * FHIR: Multiple resource types as separate isolated queries.
 * Accessibility: Section content is in nested cards with headings.
 * Performance: Parallel queries via React Query.
 * Stability: Card-level error isolation (ADR-005).
 * Legal/compliance: N/A.
 */

"use client";

import { AllergiesCard } from "@/components/cards/allergies-card";
import { CareTeamCard } from "@/components/cards/care-team-card";
import { LabResultsCard } from "@/components/cards/lab-results-card";
import { MedicationsCard } from "@/components/cards/medications-card";
import { PrescriptionsCard } from "@/components/cards/prescriptions-card";
import { ProblemListCard } from "@/components/cards/problem-list-card";
import { DashboardShell } from "@/components/layout/dashboard-shell";

export function PatientDashboard({ patientId }: { patientId: string }) {
  return (
    <DashboardShell patientId={patientId}>
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <AllergiesCard patientId={patientId} />
        <ProblemListCard patientId={patientId} />
        <MedicationsCard patientId={patientId} />
        <PrescriptionsCard patientId={patientId} />
        <CareTeamCard patientId={patientId} />
        <div className="lg:col-span-2">
          <LabResultsCard patientId={patientId} />
        </div>
      </div>
    </DashboardShell>
  );
}
