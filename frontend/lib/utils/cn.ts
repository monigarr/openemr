/**
 * @version 0.1.0
 * @date 2026-05-07
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Merge Tailwind class names with `clsx` + `tailwind-merge`.
 *
 * Usage: `import { cn } from "@/lib/utils/cn"`
 *
 * Security/PHI: N/A — no PHI.
 * HIPAA: N/A — no PHI.
 * FHIR: N/A.
 * Accessibility: N/A — non-UI.
 * Performance: O(n) over class tokens; suitable for component render paths.
 * Stability: Pure function.
 * Legal/compliance: N/A.
 */

import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}
