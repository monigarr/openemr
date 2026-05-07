/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Playwright configuration for PRD2 Modernized dashboard scenarios.
 *
 * Usage: `npm run test:e2e` from `frontend/` (start app separately or use `webServer` when wired in CI).
 *
 * Security/PHI: Tests must not print cookies, tokens, or FHIR payloads.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: Prefer role-based selectors in specs.
 * Performance: Keep workers conservative for local runs.
 * Stability: Smoke tests should run without a live OpenEMR; full flows may be skipped behind env gates.
 * Legal/compliance: N/A.
 */

import { defineConfig, devices } from "@playwright/test";

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? "http://127.0.0.1:3000";

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: [["list"]],
  webServer: {
    command: "npm run dev -- --port 3000",
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 180_000,
    env: {
      ...process.env,
      AUTH_SECRET: process.env.AUTH_SECRET ?? "0123456789abcdef0123456789abcdef",
      NEXTAUTH_URL: process.env.NEXTAUTH_URL ?? baseURL,
      AUTH_URL: process.env.AUTH_URL ?? baseURL,
      OPENEMR_OAUTH2_ISSUER: process.env.OPENEMR_OAUTH2_ISSUER ?? "https://example.invalid/oauth2/default",
      AUTH_OPENEMR_ID: process.env.AUTH_OPENEMR_ID ?? "playwright-placeholder",
      AUTH_OPENEMR_SECRET: process.env.AUTH_OPENEMR_SECRET ?? "playwright-placeholder",
      OPENEMR_BASE_URL: process.env.OPENEMR_BASE_URL ?? "https://example.invalid",
    },
  },
  use: {
    baseURL,
    trace: "on-first-retry",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
});
