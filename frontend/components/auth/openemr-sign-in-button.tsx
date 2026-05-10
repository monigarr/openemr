/**
 * @version 0.1.1
 * @date 2026-05-10
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Start OpenEMR OIDC via a real HTML form POST to `/api/auth/signin/openemr` so the browser sends the CSRF cookie (fixes MissingCSRF when `fetch`-only signIn omits cookies behind some proxies).
 *
 * Usage: Render on `/login` when OAuth env is configured; pass `disabled` when placeholders are still in use.
 *
 * Example: `<OpenEmrSignInButton disabled={needsCredentials} />`
 *
 * Dependencies: `@/components/ui/button`.
 *
 * Security/PHI: Same-origin GET `/api/auth/csrf` with credentials, then POST with double-submit CSRF; no PHI.
 * HIPAA: N/A — auth only.
 * FHIR: N/A.
 * Accessibility: Submit button; loading/disabled states.
 * Performance: N/A.
 * Stability: Full navigation on submit follows Auth.js redirect to OpenEMR (no `X-Auth-Return-Redirect`).
 * Legal/compliance: N/A.
 */

"use client";

import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";

const CALLBACK_PATH = "/dashboard";

async function fetchCsrfToken(): Promise<string> {
  const res = await fetch("/api/auth/csrf", {
    method: "GET",
    credentials: "include",
    cache: "no-store",
  });
  if (!res.ok) {
    throw new Error("csrf fetch failed");
  }
  const data = (await res.json()) as { csrfToken?: string };
  const token = typeof data.csrfToken === "string" ? data.csrfToken : "";
  if (!token) {
    throw new Error("csrf token missing");
  }
  return token;
}

export function OpenEmrSignInButton({ disabled }: { disabled: boolean }) {
  const [csrfToken, setCsrfToken] = useState<string | null>(null);
  const [loadError, setLoadError] = useState(false);

  useEffect(() => {
    if (disabled) {
      return;
    }
    let cancelled = false;
    void fetchCsrfToken()
      .then((t) => {
        if (!cancelled) {
          setCsrfToken(t);
          setLoadError(false);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setLoadError(true);
        }
      });
    return () => {
      cancelled = true;
    };
  }, [disabled]);

  if (disabled) {
    return (
      <Button type="button" className="w-full" disabled>
        Configure OAuth in .env.local first
      </Button>
    );
  }

  const ready = csrfToken !== null && !loadError;

  return (
    <div className="w-full space-y-2">
      {loadError ? (
        <p className="text-center text-sm text-red-700" role="alert">
          Could not prepare sign-in. Refresh the page or try again.
        </p>
      ) : null}
      <form method="POST" action="/api/auth/signin/openemr" className="w-full">
        <input type="hidden" name="csrfToken" value={csrfToken ?? ""} />
        <input type="hidden" name="callbackUrl" value={CALLBACK_PATH} />
        <Button type="submit" className="w-full" disabled={!ready}>
          {!ready ? "Preparing sign-in…" : "Continue to OpenEMR"}
        </Button>
      </form>
    </div>
  );
}
