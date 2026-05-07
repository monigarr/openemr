/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Augment Auth.js session/user types. Intentionally excludes access tokens from `Session` (R-001).
 *
 * Usage: Consumed automatically by TypeScript when checking `auth()` / `useSession` user fields.
 *
 * Security/PHI: Session surface must not include OAuth access tokens for client consumption.
 * HIPAA: N/A — typing only.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: Keep aligned with `lib/auth/auth.config.ts` session callback.
 * Legal/compliance: N/A.
 */

import type { DefaultSession } from "next-auth";

declare module "next-auth" {
  interface Session {
    user: DefaultSession["user"] & {
      id?: string;
    };
  }
}

declare module "next-auth/jwt" {
  interface JWT {
    accessToken?: string;
    refreshToken?: string;
    expiresAt?: number;
    error?: string;
  }
}
