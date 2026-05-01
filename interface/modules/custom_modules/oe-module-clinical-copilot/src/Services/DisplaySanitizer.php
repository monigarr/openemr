<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Defense-in-depth: reduce repeated direct identifiers in card copy (header already shows them).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final class DisplaySanitizer
{
    public function sanitizeLine(string $line): string
    {
        $s = $line;
        $s = preg_replace('/\b(MRN|mrn)\s*[#:]?\s*\d+/i', '[identifier removed]', $s) ?? $s;
        $s = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[date removed]', $s) ?? $s;
        $s = preg_replace('/\b\d{1,2}\/\d{1,2}\/\d{2,4}\b/', '[date removed]', $s) ?? $s;
        return trim($s);
    }
}
