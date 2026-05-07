/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Server Actions for authentication flows (sign-out).
 *
 * Usage: `<form action={signOutAction}>…</form>` from client layouts.
 *
 * Security/PHI: Standard session invalidation via Auth.js; no PHI in payloads.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: Redirects to `/login` after sign-out.
 * Legal/compliance: N/A.
 */

"use server";

import { signOut } from "@/auth";

export async function signOutAction(): Promise<void> {
  await signOut({ redirectTo: "/login" });
}
