/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: NextAuth.js entrypoint — exports route handlers, `auth`, `signIn`, and `signOut` for the App Router.
 *
 * Usage: Import handlers in `app/api/auth/[...nextauth]/route.ts`; import `auth` in RSC, proxy, and route handlers.
 *
 * Security/PHI: OAuth access tokens are not attached to the user-visible session object (see `lib/auth/auth.config.ts`).
 * HIPAA: N/A — auth plumbing.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: Fails fast at runtime if required OAuth env is missing (providers array empty) — configure `.env.local` for local dev.
 * Legal/compliance: N/A.
 */

import NextAuth from "next-auth";

import { authConfig } from "@/lib/auth/auth.config";

export const { handlers, auth, signIn, signOut } = NextAuth(authConfig);
