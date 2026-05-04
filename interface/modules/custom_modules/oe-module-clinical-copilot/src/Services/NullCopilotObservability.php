<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * No-op observability port (default when Langfuse is disabled).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final class NullCopilotObservability implements CopilotObservabilityPort
{
    public function beginTrace(string $traceId, CopilotTraceContext $context): void
    {
    }

    public function recordOpenAiGeneration(
        string $label,
        string $model,
        float $startMicrotime,
        float $endMicrotime,
        array $usage,
        ?string $inputPreview,
        ?string $outputPreview,
    ): void {
    }

    public function recordSpan(string $name, float $startMicrotime, float $endMicrotime, bool $ok, ?string $detail, array $metadata = []): void
    {
    }

    public function finalizeTrace(array $metadata): void
    {
    }

    public function flush(): void
    {
    }
}
