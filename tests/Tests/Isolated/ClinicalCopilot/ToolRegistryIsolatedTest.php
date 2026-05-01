<?php

/**
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
