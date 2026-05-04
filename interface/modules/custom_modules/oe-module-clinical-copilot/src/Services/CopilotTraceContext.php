<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Opaque identifiers and HTTP action for one Clinical Co-Pilot trace (no raw usernames in payloads).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final readonly class CopilotTraceContext
{
    public function __construct(
        public string $userIdOpaque,
        public string $sessionIdOpaque,
        public string $httpAction,
        public bool $pidPresent,
        /** Number of prior chat rows sent into the orchestrator (session depth; no message content). */
        public int $priorChatTurnCount = 0,
        /** Length of clinician message line in bytes (no raw text sent to Langfuse). */
        public int $userLineCharCount = 0,
    ) {
    }
}
