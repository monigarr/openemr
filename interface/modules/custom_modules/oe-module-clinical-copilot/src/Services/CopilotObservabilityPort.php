<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Port for optional LLM/trace observability (Langfuse). Implementations must be fail-open.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

interface CopilotObservabilityPort
{
    /**
     * Start a trace keyed by OpenEMR request id (correlates with AgentTelemetry logs).
     */
    public function beginTrace(string $traceId, CopilotTraceContext $context): void;

    /**
     * Record one OpenAI completion (tool-loop round or JSON fallback). Usage keys: prompt_tokens, completion_tokens, total_tokens.
     *
     * @param array<string,int> $usage
     */
    public function recordOpenAiGeneration(
        string $label,
        string $model,
        float $startMicrotime,
        float $endMicrotime,
        array $usage,
        ?string $inputPreview,
        ?string $outputPreview,
    ): void;

    /**
     * Record a synchronous span (tools, verification, domain rules, collect).
     *
     * @param array<string,mixed> $metadata
     */
    public function recordSpan(string $name, float $startMicrotime, float $endMicrotime, bool $ok, ?string $detail, array $metadata = []): void;

    /**
     * Merge terminal metadata into the trace (counts, error codes — no PHI).
     *
     * @param array<string,mixed> $metadata
     */
    public function finalizeTrace(array $metadata): void;

    /**
     * Flush queued events to the remote sink (HTTP). Safe to call multiple times.
     */
    public function flush(): void;
}
