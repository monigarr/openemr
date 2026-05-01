<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Registered agent tools (server-side execution only).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

final class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $byName = [];

    /** @var array<string, string> tool_name => merge_root_key */
    private array $mergeKeyByName = [];

    public function __construct(
        ChartListsTool $chartLists,
        RecentEncountersTool $recentEncounters,
        RecentLabsTool $recentLabs,
    ) {
        $this->register($chartLists, 'chart_lists');
        $this->register($recentEncounters, 'recent_encounters');
        $this->register($recentLabs, 'recent_labs');
    }

    private function register(ToolInterface $tool, string $mergeKey): void
    {
        $n = $tool->name();
        $this->byName[$n] = $tool;
        $this->mergeKeyByName[$n] = $mergeKey;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function openAiToolsArray(): array
    {
        $out = [];
        foreach ($this->byName as $t) {
            $out[] = $t->openAiToolDefinition();
        }
        return $out;
    }

    public function runTool(string $name, int $pid): string
    {
        if (!isset($this->byName[$name])) {
            return json_encode(['error' => 'unknown_tool', 'name' => $name], JSON_UNESCAPED_SLASHES) ?: '{"error":"unknown_tool"}';
        }
        try {
            $payload = $this->byName[$name]->execute($pid);
            $j = json_encode($payload, JSON_UNESCAPED_SLASHES);
            return $j !== false ? $j : '{"error":"encode"}';
        } catch (\Throwable $e) {
            return json_encode(['error' => 'tool_exception', 'tool' => $name], JSON_UNESCAPED_SLASHES) ?: '{"error":"tool_exception"}';
        }
    }

    /**
     * Merged bundle for citation verification (stable dot-path roots).
     *
     * @return array<string,mixed>
     */
    public function collectMerged(int $pid): array
    {
        $merged = [];
        foreach ($this->byName as $name => $tool) {
            $key = $this->mergeKeyByName[$name] ?? $name;
            $merged[$key] = $tool->execute($pid);
        }
        return $merged;
    }

    /**
     * @return list<ToolInterface>
     */
    public function all(): array
    {
        return array_values($this->byName);
    }
}
