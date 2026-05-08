/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Next.js instrumentation hook — starts Langfuse OpenTelemetry export for Track B when dashboard Langfuse is enabled.
 *
 * Usage: Automatic on Node runtime. Requires `DASHBOARD_LANGFUSE_ENABLE`, `LANGFUSE_PUBLIC_KEY`, `LANGFUSE_SECRET_KEY`, optional `LANGFUSE_BASE_URL`.
 *
 * Dependencies: `@opentelemetry/sdk-node`, `@langfuse/otel`, `lib/observability/dashboard-langfuse-processor.ts`.
 *
 * Security/PHI: Export is metadata-first at call sites; do not add PHI to spans.
 * HIPAA: Third-party subprocessor when enabled.
 * FHIR: N/A
 * Accessibility: N/A
 * Performance: SDK starts once per Node process; spans flushed after FHIR responses.
 * Stability: Fail-open — errors during setup are logged and the app continues without tracing.
 * Legal/compliance: Operator configures region / self-host per org policy.
 */

import { isDashboardLangfuseEnabled } from "@/lib/observability/is-dashboard-langfuse-enabled";

export async function register(): Promise<void> {
  if (process.env.NEXT_RUNTIME !== "nodejs") {
    return;
  }

  if (!isDashboardLangfuseEnabled()) {
    return;
  }

  try {
    const { NodeSDK } = await import("@opentelemetry/sdk-node");
    const { LangfuseSpanProcessor } = await import("@langfuse/otel");
    const { setDashboardLangfuseProcessor } = await import("@/lib/observability/dashboard-langfuse-processor");

    const processor = new LangfuseSpanProcessor({ exportMode: "immediate" });
    setDashboardLangfuseProcessor(processor);

    const sdk = new NodeSDK({
      spanProcessors: [processor],
    });
    sdk.start();
  } catch (err) {
    console.error("[dashboard-langfuse] Failed to start OpenTelemetry / Langfuse processor", err);
  }
}
