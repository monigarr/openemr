<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Optional observability wiring for a single orchestrator run (Langfuse + trace metadata).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final readonly class CopilotRunInstrumentation
{
    public function __construct(
        public CopilotObservabilityPort $observability,
        public CopilotTraceContext $traceContext,
        public string $httpAction,
    ) {
    }
}
