/**
 * @version 0.1.1
 * @date 2026-05-10
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
 * Stability: OAuth-related env is read at request time (`force-dynamic`) so platform secrets are not inlined at `next build`.
 * Legal/compliance: N/A.
 */

import { signIn } from "@/auth";
import { Button } from "@/components/ui/button";

/** Resolve OAuth env at runtime (e.g. Railway Variables), not at static generation. */
export const dynamic = "force-dynamic";

function isPlaceholderOauth(): boolean {
  const id = (process.env.AUTH_OPENEMR_ID ?? "").trim();
  const secret = (process.env.AUTH_OPENEMR_SECRET ?? "").trim();
  const idLooksLikeRedirectUri = /^https?:\/\//i.test(id);
  return (
    id === "" ||
    secret === "" ||
    id === "your-oauth-client-id" ||
    secret === "your-oauth-client-secret" ||
    id.toLowerCase().includes("your-oauth") ||
    secret.toLowerCase().includes("your-oauth") ||
    idLooksLikeRedirectUri
  );
}

export default function LoginPage() {
  const needsCredentials = isPlaceholderOauth();
  const issuer = process.env.OPENEMR_OAUTH2_ISSUER ?? "(not set)";

  return (
    <div className="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-6">
      <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 className="text-xl font-semibold text-slate-900">Sign in</h1>
        <p className="mt-2 text-sm text-slate-700">
          Continue to your OpenEMR server to sign in with OpenID Connect. Your clinical session stays on the OpenEMR trust
          boundary; this app stores only an encrypted server session cookie.
        </p>

        {needsCredentials ? (
          <div
            className="mt-4 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950"
            role="status"
          >
            <p className="font-medium">OAuth client not configured</p>
            <p className="mt-1">
              Set <code className="rounded bg-amber-100 px-1">AUTH_OPENEMR_ID</code> (the{" "}
              <strong>client ID</strong> string from the app registration — not the callback URL) and{" "}
              <code className="rounded bg-amber-100 px-1">AUTH_OPENEMR_SECRET</code> in{" "}
              <code className="rounded bg-amber-100 px-1">frontend/.env.local</code> from OpenEMR{" "}
              <strong>Admin → System → API Clients → Register New App</strong>. Redirect URI must be{" "}
              <code className="rounded bg-amber-100 px-1 break-all">
                {(process.env.NEXTAUTH_URL ?? "http://localhost:3000").replace(/\/$/, "")}/api/auth/callback/openemr
              </code>
              .
            </p>
            <p className="mt-2 text-xs text-amber-900">
              Open this app at the same host as <code className="rounded bg-amber-100 px-1">NEXTAUTH_URL</code> (e.g.{" "}
              <code className="rounded bg-amber-100 px-1">http://localhost:3000</code>
              , not <code className="rounded bg-amber-100 px-1">127.0.0.1</code> or a LAN IP) so the OAuth state cookie is
              sent on return.
            </p>
            <p className="mt-2 text-xs text-amber-900">Issuer (discovery): {issuer}</p>
          </div>
        ) : null}

        <form
          className="mt-6"
          action={async () => {
            "use server";
            await signIn("openemr", { redirectTo: "/dashboard" });
          }}
        >
          <Button type="submit" className="w-full" disabled={needsCredentials}>
            {needsCredentials ? "Configure OAuth in .env.local first" : "Continue to OpenEMR"}
          </Button>
        </form>
      </div>
    </div>
  );
}
