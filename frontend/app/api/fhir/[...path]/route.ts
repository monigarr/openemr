/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Server-side FHIR proxy. Reads OAuth access token from the encrypted Auth.js JWT (never from client JS) and forwards allowlisted GET requests to OpenEMR.
 *
 * Usage: Browser/client fetch `/api/fhir/Patient/123` (cookies included). Server attaches `Authorization: Bearer …` to `OPENEMR_BASE_URL/apis/default/fhir/...`.
 *
 * Security/PHI: SSRF allowlist in `lib/fhir/allowlist.ts`; avoid logging full URLs with patient identifiers in production. Optional Langfuse spans record resource type and HTTP status only (no ids, query strings, or bodies). Trace `userId` / `sessionId` are SHA-256 digests (`LANGFUSE_ID_SALT` + JWT `sub` / session seed), not raw identifiers.
 * HIPAA: Proxy enforces same RBAC as OpenEMR for the authenticated principal.
 * FHIR: Standard REST read traffic only (GET).
 * Accessibility: N/A.
 * Performance: Streaming not implemented; JSON only.
 * Stability: Returns 401 when session/token missing; 403 for disallowed paths.
 * Legal/compliance: N/A.
 */

import { propagateAttributes, startActiveObservation } from "@langfuse/tracing";
import { getToken } from "next-auth/jwt";
import type { JWT } from "next-auth/jwt";
import { after } from "next/server";
import type { NextRequest } from "next/server";

import { assertSafeFhirPath } from "@/lib/fhir/allowlist";
import { flushDashboardLangfuse } from "@/lib/observability/dashboard-langfuse-processor";
import { fhirProxyTraceMetadata } from "@/lib/observability/fhir-proxy-trace-metadata";
import { buildLangfuseOpaqueIdsFromJwt } from "@/lib/observability/langfuse-opaque-ids";
import { shouldTraceFhirProxyRequests } from "@/lib/observability/should-trace-fhir-proxy";

async function forwardFhir(req: NextRequest, path: string[], token: JWT | null): Promise<Response> {
  assertSafeFhirPath(path);

  const base = process.env.OPENEMR_BASE_URL;
  if (!base) {
    return Response.json(
      { resourceType: "OperationOutcome", issue: [{ severity: "error", code: "exception" }] },
      { status: 500 },
    );
  }

  if (!token?.accessToken || token.error) {
    return Response.json(
      { resourceType: "OperationOutcome", issue: [{ severity: "error", code: "login" }] },
      { status: 401 },
    );
  }

  const joined = path.join("/");
  const target = new URL(`${base.replace(/\/$/, "")}/apis/default/fhir/${joined}`);
  const incoming = new URL(req.url);
  target.search = incoming.search;

  const res = await fetch(target, {
    method: "GET",
    headers: {
      Accept: "application/fhir+json",
      Authorization: `Bearer ${token.accessToken}`,
    },
    cache: "no-store",
  });

  const body = await res.text();
  return new Response(body, {
    status: res.status,
    headers: {
      "Content-Type": res.headers.get("Content-Type") ?? "application/fhir+json",
    },
  });
}

export async function GET(
  req: NextRequest,
  context: { params: Promise<{ path: string[] }> },
): Promise<Response> {
  try {
    const { path } = await context.params;
    const traceProxy = shouldTraceFhirProxyRequests();
    const token = await getToken({
      req,
      secret: process.env.AUTH_SECRET,
      secureCookie: process.env.NODE_ENV === "production",
    });

    const response = traceProxy
      ? await startActiveObservation(
          "fhir-proxy-get",
          async (span) => {
            const opaque = buildLangfuseOpaqueIdsFromJwt(token);
            const runForward = async () => {
              span.update({
                metadata: fhirProxyTraceMetadata(path),
              });
              const res = await forwardFhir(req, path, token);
              span.update({
                metadata: {
                  ...fhirProxyTraceMetadata(path),
                  "http.status_code": String(res.status),
                },
              });
              return res;
            };

            if (opaque) {
              return await propagateAttributes(
                {
                  userId: opaque.userIdOpaque,
                  sessionId: opaque.sessionIdOpaque,
                },
                runForward,
              );
            }
            return await runForward();
          },
          { asType: "span" },
        )
      : await forwardFhir(req, path, token);

    if (traceProxy) {
      after(async () => {
        await flushDashboardLangfuse();
      });
    }

    return response;
  } catch {
    return Response.json(
      { resourceType: "OperationOutcome", issue: [{ severity: "error", code: "forbidden" }] },
      { status: 403 },
    );
  }
}
