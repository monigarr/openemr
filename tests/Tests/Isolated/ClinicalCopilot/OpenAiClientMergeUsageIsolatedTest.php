<?php

/**
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
