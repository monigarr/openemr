/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated error UI for a single clinical card (three-state pattern: error).
 *
 * Usage: Render when React Query `isError` is true; `onRetry` calls `refetch`.
 *
 * Security/PHI: Do not display exception messages that may contain server details in production.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Button is keyboard focusable; error is announced via visible text.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export function CardErrorFallback({
  title,
  onRetry,
}: {
  title: string;
  onRetry: () => void;
}): React.ReactElement {
  return (
    <Card className="border-rose-200">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <p className="text-sm text-slate-700">This section could not be loaded. Other dashboard sections may still be available.</p>
        <Button type="button" variant="outline" onClick={onRetry}>
          Retry
        </Button>
      </CardContent>
    </Card>
  );
}
