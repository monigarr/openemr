/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Auth.js configuration for OpenEMR OIDC (authorization code + PKCE). Stores tokens in the encrypted JWT only; session callback does not expose access tokens to the client (R-001).
 *
 * Usage: Composed by `auth.ts` (`NextAuth(authConfig)`). Env: see `frontend/.env.example` and `frontend/railway.env.example`.
 *
 * Example: User opens `/login`, submits server action → `signIn("openemr")` → OpenEMR consent → callback → JWT persisted in HTTP-only cookie.
 *
 * Dependencies: `next-auth`, OpenEMR OAuth2/OIDC endpoints (`OPENEMR_OAUTH2_ISSUER`).
 *
 * Security/PHI: OAuth tokens stay server-side in the JWT cookie; never added to `Session` for client bundles.
 * HIPAA: Least exposure; no PHI in OAuth client logs here.
 * FHIR: Scopes request `fhirUser` for FHIR access; `offline_access` for refresh. Omit `profile` unless userinfo name/email claims are required.
 * Accessibility: N/A — non-UI.
 * Performance: Refresh runs only near token expiry.
 * Stability: Refresh failures set `token.error` so callers can force re-auth.
 * Legal/compliance: N/A.
 */

import { randomUUID } from "node:crypto";

import type { Account, NextAuthConfig, Profile } from "next-auth";
import type { JWT } from "next-auth/jwt";

function requireEnv(name: string): string {
  const v = process.env[name];
  if (!v) {
    throw new Error(`Missing required environment variable: ${name}`);
  }
  return v;
}

function tokenEndpoint(): string {
  const explicit = process.env.OPENEMR_OAUTH2_TOKEN_URL;
  if (explicit) {
    return explicit;
  }
  const issuer = requireEnv("OPENEMR_OAUTH2_ISSUER").replace(/\/$/, "");
  return `${issuer}/token`;
}

async function refreshAccessToken(token: JWT): Promise<JWT> {
  try {
    const refreshToken = token.refreshToken;
    if (!refreshToken) {
      return { ...token, error: "MissingRefreshToken" };
    }

    const body = new URLSearchParams({
      grant_type: "refresh_token",
      refresh_token: refreshToken,
      client_id: requireEnv("AUTH_OPENEMR_ID"),
      client_secret: requireEnv("AUTH_OPENEMR_SECRET"),
    });

    const res = await fetch(tokenEndpoint(), {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });

    const json: unknown = await res.json();
    if (!res.ok) {
      return { ...token, error: "RefreshAccessTokenError" };
    }

    const parsed = json as {
      access_token?: string;
      expires_in?: number;
      refresh_token?: string;
    };

    if (!parsed.access_token) {
      return { ...token, error: "RefreshAccessTokenError" };
    }

    const expiresAt =
      Math.floor(Date.now() / 1000) + (parsed.expires_in ?? 3600);

    return {
      ...token,
      accessToken: parsed.access_token,
      refreshToken: parsed.refresh_token ?? refreshToken,
      expiresAt,
      error: undefined,
      langfuseSessionSeed: token.langfuseSessionSeed,
    };
  } catch {
    return { ...token, error: "RefreshAccessTokenError" };
  }
}

const issuer = process.env.OPENEMR_OAUTH2_ISSUER;
const clientId = process.env.AUTH_OPENEMR_ID;
const clientSecret = process.env.AUTH_OPENEMR_SECRET;

export const authConfig = {
  trustHost: true,
  pages: {
    signIn: "/login",
  },
  providers:
    issuer && clientId && clientSecret
      ? [
          {
            id: "openemr",
            name: "OpenEMR",
            type: "oidc",
            issuer,
            clientId,
            clientSecret,
            authorization: {
              params: {
                scope: "openid profile fhirUser offline_access",
              },
            },
            checks: ["pkce", "state"],
          },
        ]
      : [],
  callbacks: {
    authorized({ auth, request }) {
      const { pathname } = request.nextUrl;
      if (pathname.startsWith("/dashboard")) {
        return !!auth?.user;
      }
      return true;
    },
    async jwt({
      token,
      account,
      profile,
    }: {
      token: JWT;
      account?: Account | null;
      profile?: Profile;
    }): Promise<JWT> {
      if (account?.access_token) {
        const expiresAt =
          account.expires_at ??
          Math.floor(Date.now() / 1000) + (account.expires_in ?? 3600);
        const profileSub = profile && "sub" in profile && typeof profile.sub === "string" ? profile.sub : undefined;
        return {
          ...token,
          accessToken: account.access_token,
          refreshToken: account.refresh_token,
          expiresAt,
          error: undefined,
          name: profile?.name ?? token.name,
          email: profile?.email ?? token.email,
          sub: profileSub ?? token.sub,
          langfuseSessionSeed: token.langfuseSessionSeed ?? randomUUID(),
        };
      }

      const expiresAt = token.expiresAt;
      if (!expiresAt) {
        return token;
      }

      const refreshSecondsBefore = 60;
      if (Date.now() / 1000 < expiresAt - refreshSecondsBefore) {
        return token;
      }

      if (!token.refreshToken) {
        return { ...token, error: "MissingRefreshToken" };
      }

      return refreshAccessToken(token);
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = typeof token.sub === "string" ? token.sub : session.user.id;
        if (typeof token.name === "string") {
          session.user.name = token.name;
        }
        if (typeof token.email === "string") {
          session.user.email = token.email;
        }
      }
      return session;
    },
  },
} satisfies NextAuthConfig;
