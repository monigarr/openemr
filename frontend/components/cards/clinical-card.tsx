/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Shared wrapper enforcing loading / error / empty handling for clinical sections.
 *
 * Usage: `<ClinicalCard title="Allergies" query={query} emptyDescription="No allergies recorded." children={(bundle) => ...} />`
 *
 * Security/PHI: `children` should render text fields only; avoid dumping raw JSON.
 * HIPAA: N/A.
 * FHIR: Expects validated `FhirBundle` on success.
 * Accessibility: Loading uses `aria-busy`.
 * Performance: One query per card; failures are isolated.
 * Stability: Uses React Query states explicitly.
 * Legal/compliance: N/A.
 */

"use client";

import type { UseQueryResult } from "@tanstack/react-query";
import type { ReactElement, ReactNode } from "react";

import { CardErrorFallback } from "@/components/shared/error-fallback";
import { CardEmptyState } from "@/components/shared/empty-state";
import { CardLoadingSkeleton } from "@/components/shared/loading-skeleton";
import type { FhirBundle } from "@/lib/fhir/schemas";

export function ClinicalCard({
  title,
  emptyDescription,
  query,
  children,
}: {
  title: string;
  emptyDescription: string;
  query: UseQueryResult<FhirBundle, Error>;
  children: (bundle: FhirBundle) => ReactNode;
}): ReactElement {
  if (query.isPending) {
    return (
      <div aria-busy="true">
        <CardLoadingSkeleton title={title} />
      </div>
    );
  }

  if (query.isError) {
    return <CardErrorFallback title={title} onRetry={() => query.refetch()} />;
  }

  const bundle = query.data;
  const count = bundle.entry?.length ?? 0;
  if (count === 0) {
    return <CardEmptyState title={title} description={emptyDescription} />;
  }

  return <>{children(bundle)}</>;
}
