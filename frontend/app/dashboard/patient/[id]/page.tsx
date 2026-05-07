/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Server entry for patient dashboard (`/dashboard/patient/[id]`).
 *
 * Usage: Linked from `/dashboard` after authentication.
 *
 * Security/PHI: Page shell is non-PHI; PHI rendering occurs in client children via FHIR.
 * HIPAA: N/A.
 * FHIR: N/A (routing only).
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: Validates `id` param as a safe token for FHIR paths.
 * Legal/compliance: N/A.
 */

import { PatientDashboard } from "@/app/dashboard/patient/[id]/patient-dashboard";

const SAFE_ID = /^[A-Za-z0-9\-.]+$/;

export default async function PatientDashboardPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  if (!SAFE_ID.test(id)) {
    return (
      <div className="mx-auto max-w-2xl p-6 text-sm text-slate-800">
        Invalid patient id. Return to <a className="underline" href="/dashboard">dashboard home</a>.
      </div>
    );
  }

  return <PatientDashboard patientId={id} />;
}
