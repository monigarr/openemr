/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Holds the Langfuse OpenTelemetry span processor singleton for Track B after `instrumentation.ts` starts the NodeSDK.
 *
 * Usage: `setDashboardLangfuseProcessor()` from `instrumentation.ts` when `DASHBOARD_LANGFUSE_ENABLE` and `LANGFUSE_*` keys are set; route handlers call `flushDashboardLangfuse()` via `after()` from `next/server`.
 *
 * Example: See `app/api/fhir/[...path]/route.ts` and `instrumentation.ts`.
 *
 * Dependencies: `@langfuse/otel` (types only at call sites; instance set from instrumentation).
 *
 * Security/PHI: Processor flushes span metadata only — never put PHI in span attributes (see FHIR proxy trace metadata).
 * HIPAA: Third-party observability subprocessor when enabled; same trust bar as Track A Langfuse.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI.
 * Performance: `forceFlush` scheduled after response; use `exportMode: "immediate"` on the processor.
 * Stability: No-op when processor unset.
 * Legal/compliance: N/A — org BAA / self-host decisions apply when exporting traces.
 */

export type DashboardLangfuseFlushable = {
  forceFlush(): Promise<void>;
};

let processor: DashboardLangfuseFlushable | null = null;

export function setDashboardLangfuseProcessor(instance: DashboardLangfuseFlushable | null): void {
  processor = instance;
}

export function isDashboardLangfuseProcessorReady(): boolean {
  return processor !== null;
}

export async function flushDashboardLangfuse(): Promise<void> {
  await processor?.forceFlush();
}
