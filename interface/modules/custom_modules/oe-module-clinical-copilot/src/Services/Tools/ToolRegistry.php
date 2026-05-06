<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Registered agent tools (server-side execution only). Week 2 adds parametric tools merged dynamically per turn.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Tools;

final class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $byName = [];

    /** @var array<string, string|null> merge root key; null = no automatic merge from base collect */
    private array $mergeKeyByName = [];

    /** @var array<string, bool> */
    private array $parametricByName = [];

    public function __construct(
        ChartListsTool $chartLists,
        RecentEncountersTool $recentEncounters,
        RecentLabsTool $recentLabs,
        DocumentExtractionsTool $documentExtractions,
        RetrieveGuidelinesTool $retrieveGuidelines,
        AttachAndExtractTool $attachAndExtract,
    ) {
        $this->register($chartLists, 'chart_lists', false);
        $this->register($recentEncounters, 'recent_encounters', false);
        $this->register($recentLabs, 'recent_labs', false);
        $this->register($documentExtractions, 'document_extractions', false);
        $this->register($retrieveGuidelines, 'guideline_evidence', true);
        $this->register($attachAndExtract, null, true);
    }

    private function register(ToolInterface $tool, ?string $mergeKey, bool $parametric): void
    {
        $n = $tool->name();
        $this->byName[$n] = $tool;
        $this->mergeKeyByName[$n] = $mergeKey;
        $this->parametricByName[$n] = $parametric;
    }

    public function isParametric(string $name): bool
    {
        return $this->parametricByName[$name] ?? false;
    }

    public function mergeKeyFor(string $name): ?string
    {
        return array_key_exists($name, $this->mergeKeyByName) ? $this->mergeKeyByName[$name] : null;
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

    public function runTool(string $name, int $pid, ?string $argumentsJson = null): string
    {
        if (!isset($this->byName[$name])) {
            return json_encode(['error' => 'unknown_tool', 'name' => $name], JSON_UNESCAPED_SLASHES) ?: '{"error":"unknown_tool"}';
        }
        try {
            $payload = $this->byName[$name]->execute($pid, $argumentsJson);
            $j = json_encode($payload, JSON_UNESCAPED_SLASHES);
            return $j !== false ? $j : '{"error":"encode"}';
        } catch (\Throwable $e) {
            return json_encode(['error' => 'tool_exception', 'tool' => $name], JSON_UNESCAPED_SLASHES) ?: '{"error":"tool_exception"}';
        }
    }

    /**
     * Non-parametric tools only — used for stable citation roots before/after the model turn.
     *
     * @return array<string,mixed>
     */
    public function collectMergedBase(int $pid): array
    {
        $merged = [];
        foreach ($this->byName as $name => $tool) {
            if (($this->parametricByName[$name] ?? false) === true) {
                continue;
            }
            $key = $this->mergeKeyByName[$name] ?? null;
            if ($key === null || $key === '') {
                continue;
            }
            $merged[$key] = $tool->execute($pid, null);
        }
        return $merged;
    }

    /**
     * Backward-compatible alias: base merge only (PRD 2 turns use collectMergedBase + dynamic parametric outputs).
     *
     * @return array<string,mixed>
     */
    public function collectMerged(int $pid): array
    {
        return $this->collectMergedBase($pid);
    }

    /**
     * @return list<ToolInterface>
     */
    public function all(): array
    {
        return array_values($this->byName);
    }
}
