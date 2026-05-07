/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Verify the FHIR proxy rejects unauthenticated reads (security boundary for clinical resources).
 *
 * Usage: `npm run test:e2e`
 *
 * Security/PHI: Ensures anonymous callers cannot retrieve Patient resources via the proxy.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: Expects 401 from `/api/fhir/*` without session cookie.
 * Legal/compliance: N/A.
 */

import { expect, test } from "@playwright/test";

test.describe("Scenario 2 — nurse allergy check (unauthenticated boundary)", () => {
  test("FHIR proxy returns 401 without a session", async ({ request }) => {
    const res = await request.get("/api/fhir/AllergyIntolerance?patient=1");
    expect(res.status()).toBe(401);
    const text = await res.text();
    expect(text.toLowerCase()).not.toContain("access_token");
    expect(text.toLowerCase()).not.toContain("bearer ");
  });
});
