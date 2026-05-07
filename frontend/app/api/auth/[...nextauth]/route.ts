/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Auth.js HTTP handlers (`/api/auth/*`).
 *
 * Usage: Routed automatically by Next.js App Router.
 *
 * Security/PHI: Standard OAuth/OIDC endpoints; do not log query params containing secrets.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { handlers } from "@/auth";

export const { GET, POST } = handlers;
