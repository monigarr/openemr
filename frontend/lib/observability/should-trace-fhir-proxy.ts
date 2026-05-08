/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: True only when Langfuse env is enabled and the OpenTelemetry processor was registered (avoids observations without an exporter).
 *
 * Usage: FHIR proxy route; `instrumentation.ts` sets the processor when starting the NodeSDK.
 *
 * Security/PHI: N/A
 * HIPAA: N/A
 * FHIR: N/A
 * Accessibility: N/A
 * Performance: N/A
 * Stability: N/A
 * Legal/compliance: N/A
 */

import { isDashboardLangfuseProcessorReady } from "./dashboard-langfuse-processor";
import { isDashboardLangfuseEnabled } from "./is-dashboard-langfuse-enabled";

export function shouldTraceFhirProxyRequests(): boolean {
  return isDashboardLangfuseEnabled() && isDashboardLangfuseProcessorReady();
}
