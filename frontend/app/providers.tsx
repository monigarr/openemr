/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: App-wide client providers (React Query).
 *
 * Usage: Imported from `app/layout.tsx`.
 *
 * Security/PHI: N/A.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

"use client";

import type { ReactNode } from "react";

import { QueryProvider } from "@/hooks/query-provider";

export function AppProviders({ children }: { children: ReactNode }) {
  return <QueryProvider>{children}</QueryProvider>;
}
