<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Document extraction factory defaults to stub without Gemini credentials.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\DocumentExtractionPipelineFactory;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\StubDocumentExtractionPipeline;
use PHPUnit\Framework\TestCase;

class DocumentExtractionPipelineFactoryIsolatedTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CLINICAL_COPILOT_EXTRACTION_PIPELINE=');
        putenv('CLINICAL_COPILOT_GEMINI_API_KEY=');
        parent::tearDown();
    }

    public function testCreateDefaultWithoutEnvIsStub(): void
    {
        $p = DocumentExtractionPipelineFactory::createDefault();
        $this->assertInstanceOf(StubDocumentExtractionPipeline::class, $p);
    }

    public function testGeminiModeWithoutKeyFallsBackToStub(): void
    {
        putenv('CLINICAL_COPILOT_EXTRACTION_PIPELINE=gemini');
        putenv('CLINICAL_COPILOT_GEMINI_API_KEY');
        $p = DocumentExtractionPipelineFactory::createDefault();
        $this->assertInstanceOf(StubDocumentExtractionPipeline::class, $p);
    }
}
