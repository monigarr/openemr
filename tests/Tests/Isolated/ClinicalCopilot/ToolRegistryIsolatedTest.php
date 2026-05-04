<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated tests for `ToolRegistry` — validates merged chart-context payload shape for pid `0`
 * (no DB) and non-empty OpenAI tool definitions for chart list / encounters / labs tools (PRD 1).
 *
 * Usage: Run when changing tool registration, `collectMerged()` contract, or OpenAI tool schema assembly.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/ToolRegistryIsolatedTest.php
 *
 * Dependencies: `ToolRegistry`, `ChartListsTool`, `ChartContextTool`, `RecentEncountersTool`, `RecentLabsTool`, PHPUnit.
 *
 * Security/PHI: Uses invalid pid path only; expects defensive `invalid_pid` note, not live chart reads.
 * HIPAA: N/A — synthetic pid `0` fixture path documents safe degradation without touching PHI stores.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test.
 * Performance: No external calls in these scenarios; registry wiring should stay lightweight.
 * Stability: Asserts stable keys on merged bundle and tool array entries (`type` => `function`).
 * Legal/compliance: OpenEMR GPLv3.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\ChartContextTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ChartListsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentEncountersTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentLabsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ToolRegistry;
use PHPUnit\Framework\TestCase;

class ToolRegistryIsolatedTest extends TestCase
{
    public function testCollectMergedPidZeroDoesNotRequireDatabase(): void
    {
        $registry = new ToolRegistry(
            new ChartListsTool(new ChartContextTool()),
            new RecentEncountersTool(),
            new RecentLabsTool(),
        );
        $merged = $registry->collectMerged(0);
        $this->assertArrayHasKey('chart_lists', $merged);
        $this->assertArrayHasKey('recent_encounters', $merged);
        $this->assertArrayHasKey('recent_labs', $merged);
        $this->assertSame('invalid_pid', $merged['chart_lists']['note'] ?? null);
    }

    public function testOpenAiToolsArrayNonEmpty(): void
    {
        $registry = new ToolRegistry(
            new ChartListsTool(new ChartContextTool()),
            new RecentEncountersTool(),
            new RecentLabsTool(),
        );
        $tools = $registry->openAiToolsArray();
        $this->assertCount(3, $tools);
        $this->assertSame('function', $tools[0]['type'] ?? null);
    }
}
