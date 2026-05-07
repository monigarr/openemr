/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: React Query provider for clinical hooks (client-side refetch, isolated card failures).
 *
 * Usage: Wrap app in `app/layout.tsx` via `<QueryProvider>{children}</QueryProvider>`.
 *
 * Security/PHI: Queries must target `/api/fhir` only — never embed bearer tokens in client fetch code.
 * HIPAA: N/A — transport only.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: Default staleTime tuned for short clinical sessions.
 * Stability: `throwOnError: false` per-query in hooks to avoid whole-tree crashes.
 * Legal/compliance: N/A.
 */

"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState, type ReactNode } from "react";

export function QueryProvider({ children }: { children: ReactNode }): React.ReactElement {
  const [client] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            retry: 1,
            refetchOnWindowFocus: false,
          },
        },
      }),
  );

  return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
}
