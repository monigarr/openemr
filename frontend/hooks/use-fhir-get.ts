/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Typed React Query wrapper for GET `/api/fhir/*` (cookie session; no bearer token in JS).
 *
 * Usage: `useFhirGet(["patient", id], `Patient/${encodeURIComponent(id)}`)`
 *
 * Security/PHI: Do not log JSON bodies in the browser.
 * HIPAA: N/A.
 * FHIR: Read-only GETs via proxy.
 * Accessibility: N/A.
 * Performance: `staleTime` inherited from QueryProvider.
 * Stability: Throws on non-OK HTTP so cards can show error UI.
 * Legal/compliance: N/A.
 */

"use client";

import { useQuery, type UseQueryResult } from "@tanstack/react-query";

export async function fhirGetJson(path: string): Promise<unknown> {
  const normalized = path.replace(/^\/+/, "");
  const res = await fetch(`/api/fhir/${normalized}`, {
    method: "GET",
    credentials: "include",
    cache: "no-store",
  });

  if (!res.ok) {
    throw new Error(`FHIR request failed (${res.status})`);
  }

  return res.json();
}

export function useFhirGet<T>(
  queryKey: ReadonlyArray<unknown>,
  path: string,
  select: (json: unknown) => T,
): UseQueryResult<T, Error> {
  return useQuery({
    queryKey: [...queryKey, path],
    queryFn: async () => select(await fhirGetJson(path)),
  });
}
