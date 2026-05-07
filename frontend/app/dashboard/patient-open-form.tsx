/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Client form to navigate to `/dashboard/patient/[id]` using a validated Patient id token.
 *
 * Usage: Rendered on `/dashboard`.
 *
 * Security/PHI: Patient identifiers are sensitive; this control does not log values.
 * HIPAA: N/A — navigation only.
 * FHIR: Expects `Patient.id` compatible token for proxy allowlisting.
 * Accessibility: Labeled input and submit control.
 * Performance: N/A.
 * Stability: Refuses invalid tokens before navigation.
 * Legal/compliance: N/A.
 */

"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

const SAFE_ID = /^[A-Za-z0-9\-.]+$/;

export function PatientOpenForm() {
  const router = useRouter();
  const [patientId, setPatientId] = useState("");
  const [error, setError] = useState<string | null>(null);

  return (
    <form
      className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
      onSubmit={(e) => {
        e.preventDefault();
        const trimmed = patientId.trim();
        if (!SAFE_ID.test(trimmed)) {
          setError("Enter a valid patient id (letters, numbers, dots, hyphens).");
          return;
        }
        setError(null);
        router.push(`/dashboard/patient/${encodeURIComponent(trimmed)}`);
      }}
    >
      <div className="flex-1">
        <label htmlFor="patientId" className="block text-sm font-medium text-slate-800">
          Patient id
        </label>
        <input
          id="patientId"
          value={patientId}
          onChange={(ev) => setPatientId(ev.target.value)}
          className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          placeholder="e.g. 1"
          autoComplete="off"
        />
        {error ? <p className="mt-2 text-sm text-rose-700">{error}</p> : null}
      </div>
      <button
        type="submit"
        className="inline-flex h-10 items-center justify-center rounded-md bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800"
      >
        Open patient
      </button>
    </form>
  );
}
