/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Card-sized loading skeleton (three-state pattern: loading).
 *
 * Usage: Render while React Query `isPending` is true.
 *
 * Security/PHI: N/A.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Parent should set `aria-busy="true"` when shown.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export function CardLoadingSkeleton({ title }: { title: string }): React.ReactElement {
  return (
    <Card>
      <CardHeader>
        <Skeleton className="h-5 w-40" aria-hidden="true" />
        <span className="sr-only">Loading {title}</span>
      </CardHeader>
      <CardContent className="space-y-2">
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-5/6" />
        <Skeleton className="h-4 w-2/3" />
      </CardContent>
    </Card>
  );
}
