<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Shared truncation for server-side telemetry strings (logs + optional Langfuse previews).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final class TelemetryText
{
    /**
     * Clip string length for observability sinks (aligned with legacy AgentTelemetry behavior).
     */
    public static function clipForLog(string $value, int $maxLength = 500): string
    {
        if ($maxLength < 1) {
            return '';
        }
        if (strlen($value) <= $maxLength) {
            return $value;
        }
        return substr($value, 0, $maxLength) . '…';
    }
}
