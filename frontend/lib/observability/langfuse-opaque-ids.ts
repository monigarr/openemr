/**
 * @version 0.1.0
 * @date 2026-05-08
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Derive Langfuse `userId` / `sessionId` as SHA-256 hex digests with `LANGFUSE_ID_SALT`, aligned with Clinical Co-Pilot PHP (`CopilotRequestController` opaque hashing).
 *
 * Usage: Track B FHIR proxy traces only; call with decoded Auth.js JWT (`getToken`). Omit Langfuse user/session when JWT missing or has no `sub`.
 *
 * Example: `buildLangfuseOpaqueIdsFromJwt(token)` → `{ userIdOpaque, sessionIdOpaque }` (64-char hex each).
 *
 * Dependencies: `node:crypto`, `LANGFUSE_ID_SALT` (optional; empty string if unset), JWT fields `sub`, `langfuseSessionSeed`.
 *
 * Security/PHI: Never send raw `sub` or session seed to Langfuse — only digests. `sub` is an OIDC identifier, not clinical PHI, but still treated as sensitive for correlation.
 * HIPAA: Minimum necessary; supports session grouping without exporting raw identities.
 * FHIR: N/A
 * Accessibility: N/A
 * Performance: Two SHA-256 per request when tracing.
 * Stability: Legacy JWTs without `langfuseSessionSeed` use a fixed fallback label so hashes stay stable until re-auth.
 * Legal/compliance: Match Track A env (`LANGFUSE_ID_SALT`) for operational consistency.
 */

import { createHash } from "node:crypto";

import type { JWT } from "next-auth/jwt";

function sha256Hex(value: string): string {
  return createHash("sha256").update(value, "utf8").digest("hex");
}

function langfuseIdSalt(): string {
  return process.env.LANGFUSE_ID_SALT ?? "";
}

/** Stable per login chain when `langfuseSessionSeed` is absent (pre-migration JWTs). */
const LEGACY_SESSION_FALLBACK = "legacy-jwt-no-langfuse-session-seed";

export type LangfuseOpaqueIds = {
  userIdOpaque: string;
  sessionIdOpaque: string;
};

export function buildLangfuseOpaqueIdsFromJwt(token: JWT | null): LangfuseOpaqueIds | null {
  if (token === null) {
    return null;
  }

  const sub = typeof token.sub === "string" && token.sub.length > 0 ? token.sub : null;
  if (sub === null) {
    return null;
  }

  const salt = langfuseIdSalt();
  const userIdOpaque = sha256Hex(`${sub}\0${salt}`);

  const sessionSeed =
    typeof token.langfuseSessionSeed === "string" && token.langfuseSessionSeed.length > 0
      ? token.langfuseSessionSeed
      : LEGACY_SESSION_FALLBACK;
  const sessionIdOpaque = sha256Hex(`${sub}\0${sessionSeed}\0${salt}`);

  return { userIdOpaque, sessionIdOpaque };
}
