<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated tests for `ToolRegistry` — Week 2 parametric + base merge wiring.
 *
 * Usage:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/ToolRegistryIsolatedTest.php
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\ChartContextTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\InMemoryDocumentExtractionsStore;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\StubDocumentExtractionPipeline;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirDocumentReferenceDraftBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirObservationDraftBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\GuidelineChunkRepository;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\HybridGuidelineRetriever;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\PassThroughReranker;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\AttachAndExtractTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ChartListsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\DocumentExtractionsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentEncountersTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentLabsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RetrieveGuidelinesTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ToolRegistry;
use PHPUnit\Framework\TestCase;

class ToolRegistryIsolatedTest extends TestCase
{
    private function makeRegistry(InMemoryDocumentExtractionsStore $store): ToolRegistry
    {
        $retriever = new HybridGuidelineRetriever(new GuidelineChunkRepository(), new PassThroughReranker());
        return new ToolRegistry(
            new ChartListsTool(new ChartContextTool()),
            new RecentEncountersTool(),
            new RecentLabsTool(),
            new DocumentExtractionsTool($store),
            new RetrieveGuidelinesTool($retriever),
            new AttachAndExtractTool(
                $store,
                new StubDocumentExtractionPipeline(),
                new FhirObservationDraftBuilder(),
                new FhirDocumentReferenceDraftBuilder(),
            ),
        );
    }

    public function testCollectMergedPidZeroDoesNotRequireDatabase(): void
    {
        $registry = $this->makeRegistry(new InMemoryDocumentExtractionsStore());
        $merged = $registry->collectMergedBase(0);
        $this->assertArrayHasKey('chart_lists', $merged);
        $this->assertArrayHasKey('recent_encounters', $merged);
        $this->assertArrayHasKey('recent_labs', $merged);
        $this->assertArrayHasKey('document_extractions', $merged);
        $this->assertSame('invalid_pid', $merged['chart_lists']['note'] ?? null);
        $this->assertArrayNotHasKey('guideline_evidence', $merged);
    }

    public function testOpenAiToolsArrayNonEmpty(): void
    {
        $registry = $this->makeRegistry(new InMemoryDocumentExtractionsStore());
        $tools = $registry->openAiToolsArray();
        $this->assertCount(6, $tools);
        $this->assertSame('function', $tools[0]['type'] ?? null);
    }

    public function testRetrieveGuidelinesParametricMergeKey(): void
    {
        $registry = $this->makeRegistry(new InMemoryDocumentExtractionsStore());
        $this->assertTrue($registry->isParametric('retrieve_guidelines'));
        $this->assertSame('guideline_evidence', $registry->mergeKeyFor('retrieve_guidelines'));
        $this->assertNull($registry->mergeKeyFor('attach_and_extract'));
    }
}
