/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Login entrypoint that starts the OpenEMR OIDC flow via Auth.js (`signIn("openemr")`).
 *
 * Usage: Navigating to `/login` shows the button; successful auth lands on `/dashboard`.
 *
 * Security/PHI: No PHI on this page; OAuth handled by OpenEMR + Auth.js.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Single primary action; clear heading.
 * Performance: N/A.
 * Stability: Requires OAuth env configuration (`frontend/.env.example`).
 * Legal/compliance: N/A.
 */

import { signIn } from "@/auth";
import { Button } from "@/components/ui/button";

export default function LoginPage() {
  return (
    <div className="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-6">
      <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 className="text-xl font-semibold text-slate-900">Sign in</h1>
        <p className="mt-2 text-sm text-slate-700">
          Continue to your OpenEMR server to sign in with OpenID Connect. Your clinical session stays on the OpenEMR trust
          boundary; this app stores only an encrypted server session cookie.
        </p>

        <form
          className="mt-6"
          action={async () => {
            "use server";
            await signIn("openemr", { redirectTo: "/dashboard" });
          }}
        >
          <Button type="submit" className="w-full">
            Continue to OpenEMR
          </Button>
        </form>
      </div>
    </div>
  );
}
