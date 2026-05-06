<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated tests for hybrid guideline retrieval over bundled corpus.
 *
 * Usage:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/HybridGuidelineRetrieverIsolatedTest.php
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\GuidelineChunkRepository;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\HybridGuidelineRetriever;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\PassThroughReranker;
use PHPUnit\Framework\TestCase;

class HybridGuidelineRetrieverIsolatedTest extends TestCase
{
    public function testRetrieveDiabetesQueryReturnsChunk(): void
    {
        $r = new HybridGuidelineRetriever(new GuidelineChunkRepository(), new PassThroughReranker());
        $out = $r->retrieve('HbA1c diabetes follow-up targets', 5, 3);
        $this->assertArrayHasKey('chunks', $out);
        $this->assertNotSame([], $out['chunks']);
        $first = $out['chunks'][0];
        $this->assertArrayHasKey('text', $first);
        $this->assertArrayHasKey('chunk_id', $first);
        $this->assertSame('sparse_lexical+dense_deterministic', $out['hybrid_note'] ?? null);
        $ids = array_map(static fn (array $c): string => (string) ($c['chunk_id'] ?? ''), $out['chunks']);
        $this->assertContains('glycemic-targets-001', $ids, 'Hybrid retrieval should surface the diabetes/glycemic chunk');
    }
}
