/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Loading skeleton primitive for cards and banner.
 *
 * Usage: `<Skeleton className="h-4 w-full" />`
 *
 * Security/PHI: N/A.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Use only for loading states; ensure `aria-busy` on parent where appropriate.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { cn } from "@/lib/utils/cn";

export function Skeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>): React.ReactElement {
  return <div className={cn("animate-pulse rounded-md bg-slate-200", className)} {...props} />;
}
