/**
 * @version 0.1.0
 * @date 2026-05-10
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Start OpenEMR OIDC via Auth.js client `signIn` so PKCE/state cookies are set on the `/api/auth/*` route response (avoids Server Action + `cookies().set` redirect races on Railway).
 *
 * Usage: Render on `/login` when OAuth env is configured; pass `disabled` when placeholders are still in use.
 *
 * Example: `<OpenEmrSignInButton disabled={needsCredentials} />`
 *
 * Dependencies: `next-auth/react`, `@/components/ui/button`.
 *
 * Security/PHI: Same-origin POST to `/api/auth/signin/openemr` with CSRF token; no PHI.
 * HIPAA: N/A — auth only.
 * FHIR: N/A.
 * Accessibility: Native button; label reflects disabled state.
 * Performance: N/A.
 * Stability: Relies on Auth.js client transport; no SessionProvider required for `signIn`.
 * Legal/compliance: N/A.
 */

"use client";

import { signIn } from "next-auth/react";

import { Button } from "@/components/ui/button";

export function OpenEmrSignInButton({ disabled }: { disabled: boolean }) {
  return (
    <Button
      type="button"
      className="w-full"
      disabled={disabled}
      onClick={() => {
        if (!disabled) {
          void signIn("openemr", { callbackUrl: "/dashboard" });
        }
      }}
    >
      {disabled ? "Configure OAuth in .env.local first" : "Continue to OpenEMR"}
    </Button>
  );
}
