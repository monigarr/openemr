/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Keep Auth.js session fresh and enforce auth for `/dashboard/**` via `callbacks.authorized`.
 *
 * Usage: Committed as Next.js root proxy; matcher limits scope to dashboard routes.
 *
 * Security/PHI: Unauthenticated users are redirected to the configured sign-in page; no PHI in proxy.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: Runs only on matched routes.
 * Stability: Relies on Auth.js defaults for redirect-to-sign-in when `authorized` returns false.
 * Legal/compliance: N/A.
 */

export { auth as proxy } from "@/auth";

export const config = {
  matcher: ["/dashboard/:path*"],
};
