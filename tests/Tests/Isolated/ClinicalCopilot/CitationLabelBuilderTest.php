<?php

/**
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use OpenEMR\Modules\ClinicalCopilot\Services\CitationLabelBuilder;
use PHPUnit\Framework\TestCase;

class CitationLabelBuilderTest extends TestCase
{
    public function testLabelsAvoidEchoingValues(): void
    {
        $b = new CitationLabelBuilder();
        $merged = [
            'chart_lists' => ['allergies' => [['title' => 'SECRET_VALUE']]],
            'recent_encounters' => ['encounters' => [['date' => '2020-01-01', 'reason' => 'X']]],
        ];
        $labels = $b->labelsForPaths($merged, ['chart_lists.allergies.0.title', 'recent_encounters.encounters.0.reason']);
        $this->assertSame('Allergy list', $labels[0]['label']);
        $this->assertStringContainsString('Recent encounter', $labels[1]['label']);
        $this->assertStringNotContainsString('SECRET_VALUE', $labels[0]['label']);
    }
}
