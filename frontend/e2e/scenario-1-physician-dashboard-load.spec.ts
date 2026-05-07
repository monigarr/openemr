/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Smoke scenario — unauthenticated users are routed to sign-in; login surface renders.
 *
 * Usage: `npm run test:e2e` (requires dev server reachable at PLAYWRIGHT_BASE_URL).
 *
 * Security/PHI: No PHI assertions.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Uses role/name selectors.
 * Performance: N/A.
 * Stability: Depends on Auth.js redirect behavior for protected routes.
 * Legal/compliance: N/A.
 */

import { expect, test } from "@playwright/test";

test.describe("Scenario 1 — physician dashboard load (smoke)", () => {
  test("root redirects to login when no session cookie is present", async ({ page }) => {
    await page.goto("/");
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole("heading", { name: /sign in/i })).toBeVisible();
  });

  test("dashboard requires authentication", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/\/login/);
  });
});
