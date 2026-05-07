/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Distinct empty state copy (three-state pattern: empty vs loading vs error).
 *
 * Usage: When a valid FHIR bundle has zero entries.
 *
 * Security/PHI: N/A.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Clear, readable text.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export function CardEmptyState({ title, description }: { title: string; description: string }): React.ReactElement {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="text-sm text-slate-600">{description}</p>
      </CardContent>
    </Card>
  );
}
