<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated PHPUnit coverage for `OpenAiClient::mergeUsageTokens()` — aggregates token usage
 * counters across multi-call flows for observability and quota tracking in Clinical CoPilot.
 *
 * Usage: Invoke PHPUnit on this file when changing usage accounting or OpenAI client plumbing.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalCopilot/OpenAiClientMergeUsageIsolatedTest.php
 *
 * Dependencies: `OpenEMR\Modules\ClinicalCopilot\Services\OpenAiClient`, PHPUnit `TestCase`.
 *
 * Security/PHI: No patient data; numeric usage maps only.
 * HIPAA: N/A — no PHI.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test.
 * Performance: Constant-time merge; safe for high-frequency calls in production.
 * Stability: Locks expected additive semantics for prompt/completion/total token fields.
 * Legal/compliance: OpenEMR GPLv3; usage metrics may feed operational logs — keep PHI out of those logs in app code.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\OpenAiClient;
use PHPUnit\Framework\TestCase;

class OpenAiClientMergeUsageIsolatedTest extends TestCase
{
    public function testMergeUsageTokens(): void
    {
        $a = ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15];
        $b = ['prompt_tokens' => 3, 'completion_tokens' => 2, 'total_tokens' => 5];
        $m = OpenAiClient::mergeUsageTokens($a, $b);
        $this->assertSame(13, $m['prompt_tokens']);
        $this->assertSame(7, $m['completion_tokens']);
        $this->assertSame(20, $m['total_tokens']);
    }
}
