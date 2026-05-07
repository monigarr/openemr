/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Root route redirect — authenticated users go to `/dashboard`, otherwise `/login`.
 *
 * Usage: `/`
 *
 * Security/PHI: N/A.
 * HIPAA: N/A.
 * FHIR: N/A.
 * Accessibility: N/A.
 * Performance: N/A.
 * Stability: N/A.
 * Legal/compliance: N/A.
 */

import { redirect } from "next/navigation";

import { auth } from "@/auth";

export default async function Home() {
  const session = await auth();
  if (session) {
    redirect("/dashboard");
  }
  redirect("/login");
}
