<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

interface ToolInterface
{
    public function name(): string;

    /**
     * OpenAI Chat Completions `tools[]` entry (type function).
     *
     * @return array<string,mixed>
     */
    public function openAiToolDefinition(): array;

    /**
     * Bounded JSON-serializable payload for verification (citation roots under tool key in merged bundle).
     *
     * @param string|null $argumentsJson OpenAI tool `function.arguments` JSON string for parametric tools.
     * @return array<string,mixed>
     */
    public function execute(int $pid, ?string $argumentsJson = null): array;
}
