/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Post-login navigation helper to open a patient dashboard by OpenEMR FHIR `Patient.id`.
 *
 * Usage: User enters an id and navigates to `/dashboard/patient/{id}`.
 *
 * Security/PHI: Does not display PHI; patient id is entered by the authenticated user.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Form controls are labeled (`PatientOpenForm`).
 * Performance: N/A.
 * Stability: Client-side navigation with token validation.
 * Legal/compliance: N/A.
 */

import { PatientOpenForm } from "@/app/dashboard/patient-open-form";
import { signOutAction } from "@/app/actions/auth";
import { auth } from "@/auth";
import { Button } from "@/components/ui/button";

export default async function DashboardHomePage() {
  const session = await auth();

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
          <div>
            <div className="text-sm font-medium text-slate-500">OpenEMR</div>
            <div className="text-lg font-semibold text-slate-900">Patient Dashboard</div>
          </div>
          <form action={signOutAction}>
            <Button type="submit" variant="outline">
              Sign out
            </Button>
          </form>
        </div>
      </header>

      <div className="mx-auto max-w-2xl space-y-6 p-6">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Dashboard home</h1>
          <p className="mt-2 text-sm text-slate-700">
            Signed in as{" "}
            <span className="font-medium">{session?.user?.email ?? session?.user?.name ?? "OpenEMR user"}</span>.
          </p>
        </div>

        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <h2 className="text-base font-semibold text-slate-900">Open a patient</h2>
          <p className="mt-2 text-sm text-slate-700">
            Enter a FHIR <span className="font-medium">Patient.id</span> from your OpenEMR instance, then open the
            modernized patient dashboard.
          </p>
          <PatientOpenForm />
        </div>

        <p className="text-xs text-slate-600">
          This UI is a presentation-layer port; authorization and RBAC are enforced by OpenEMR APIs for your session.
        </p>
      </div>
    </div>
  );
}
