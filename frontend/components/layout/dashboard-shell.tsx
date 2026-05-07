/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Authenticated dashboard chrome (title, sign-out, persistent patient banner slot, main content grid).
 *
 * Usage: Wrap patient dashboard pages.
 *
 * Security/PHI: Sign-out clears session; no PHI in chrome text.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Header actions are keyboard reachable.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

"use client";

import type { ReactNode } from "react";

import { signOutAction } from "@/app/actions/auth";
import { PatientBanner } from "@/components/layout/patient-banner";
import { Button } from "@/components/ui/button";

export function DashboardShell({ patientId, children }: { patientId: string; children: ReactNode }) {
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

      <PatientBanner patientId={patientId} />

      <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
    </div>
  );
}
