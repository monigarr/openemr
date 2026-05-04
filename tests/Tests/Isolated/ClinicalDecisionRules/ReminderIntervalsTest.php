<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated PHPUnit tests for CDR `ReminderIntervals` — aggregation of reminder interval details by type/range,
 * uniqueness of types, lookup helpers, and human-readable `displayDetails()` output (translation disabled in setup).
 *
 * Usage: Run when changing reminder interval rule library models or display formatting; uses `$GLOBALS['disable_translation']` in setUp/tearDown.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalDecisionRules/ReminderIntervalsTest.php
 *
 * Dependencies: `ReminderIntervals`, `ReminderIntervalDetail`, `ReminderIntervalRange`, `ReminderIntervalType`, `TimeUnit`, PHPUnit.
 *
 * Security/PHI: Fixtures are synthetic intervals only; no chart or demographic data.
 * HIPAA: N/A — tests; production reminders tie to clinical workflows — keep PHI out of logs when integrating.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test (`displayDetails` strings affect UI copy upstream).
 * Performance: Small in-memory collections; suitable for rule-builder UI scale.
 * Stability: Documents PHPStan suppressions around upstream `from()` docblocks; preserve when refactoring factories.
 * Legal/compliance: OpenEMR GPLv3.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Tests\Isolated\ClinicalDecisionRules;

use OpenEMR\ClinicalDecisionRules\Interface\RuleLibrary\ReminderIntervalDetail;
use OpenEMR\ClinicalDecisionRules\Interface\RuleLibrary\ReminderIntervalRange;
use OpenEMR\ClinicalDecisionRules\Interface\RuleLibrary\ReminderIntervals;
use OpenEMR\ClinicalDecisionRules\Interface\RuleLibrary\ReminderIntervalType;
use OpenEMR\ClinicalDecisionRules\Interface\RuleLibrary\TimeUnit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('isolated')]
class ReminderIntervalsTest extends TestCase
{
    private ReminderIntervals $intervals;

    protected function setUp(): void
    {
        // xl() returns its input when translation is disabled
        $GLOBALS['disable_translation'] = true;
        $this->intervals = new ReminderIntervals();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['disable_translation']);
    }

    /**
     * Create a ReminderIntervalDetail. The source classes ReminderIntervalRange::from()
     * and TimeUnit::from() have incorrect @return docblocks (they declare
     * ReminderIntervalType) so PHPStan reports type mismatches here.
     */
    private function makeDetail(
        ReminderIntervalType $type,
        string $rangeCode,
        int $amount,
        string $unitCode
    ): ReminderIntervalDetail {
        $range = ReminderIntervalRange::from($rangeCode);
        $unit = TimeUnit::from($unitCode);
        return new ReminderIntervalDetail(
            $type,
            $range, // @phpstan-ignore argument.type
            $amount,
            $unit, // @phpstan-ignore argument.type
        );
    }

    public function testAddDetailAndGetTypes(): void
    {
        $type = ReminderIntervalType::from('clinical');
        $this->intervals->addDetail($this->makeDetail($type, 'pre', 3, 'month'));

        /** @var list<ReminderIntervalType> $types */
        $types = $this->intervals->getTypes();

        $this->assertCount(1, $types);
        $this->assertSame('clinical', $types[0]->code);
    }

    public function testGetTypesReturnsUniqueTypes(): void
    {
        $clinical = ReminderIntervalType::from('clinical');
        $this->intervals->addDetail($this->makeDetail($clinical, 'pre', 1, 'month'));
        $this->intervals->addDetail($this->makeDetail($clinical, 'post', 2, 'month'));

        /** @var list<ReminderIntervalType> $types */
        $types = $this->intervals->getTypes();
        $this->assertCount(1, $types);
    }

    public function testGetDetailForTypeReturnsAllDetails(): void
    {
        $clinical = ReminderIntervalType::from('clinical');
        $this->intervals->addDetail($this->makeDetail($clinical, 'pre', 1, 'month'));
        $this->intervals->addDetail($this->makeDetail($clinical, 'post', 2, 'month'));

        /** @var list<ReminderIntervalDetail> $details */
        $details = $this->intervals->getDetailFor($clinical);
        $this->assertCount(2, $details);
    }

    public function testGetDetailForTypeAndRangeReturnsSingleDetail(): void
    {
        $clinical = ReminderIntervalType::from('clinical');
        $pre = ReminderIntervalRange::from('pre');
        $this->intervals->addDetail($this->makeDetail($clinical, 'pre', 1, 'month'));
        $this->intervals->addDetail($this->makeDetail($clinical, 'post', 2, 'month'));

        /** @var ReminderIntervalDetail $detail  @phpstan-ignore varTag.type */
        $detail = $this->intervals->getDetailFor($clinical, $pre); // @phpstan-ignore argument.type
        $this->assertSame(1, $detail->amount);
    }

    public function testGetDetailForMissingTypeReturnsNull(): void
    {
        $patient = ReminderIntervalType::from('patient');
        $result = $this->intervals->getDetailFor($patient);

        // getDetailFor() returns null for unknown types, but its docblock
        // declares @return array so PHPStan thinks null is impossible.
        $this->assertNull($result); // @phpstan-ignore method.impossibleType
    }

    public function testDisplayDetailsFormatsCommaSeparated(): void
    {
        $clinical = ReminderIntervalType::from('clinical');
        $this->intervals->addDetail($this->makeDetail($clinical, 'pre', 1, 'month'));
        $this->intervals->addDetail($this->makeDetail($clinical, 'post', 2, 'week'));

        /** @var string $display */
        $display = $this->intervals->displayDetails($clinical);

        // With disable_translation, xl() returns input unchanged
        $this->assertStringContainsString('Warning', $display);
        $this->assertStringContainsString('Past due', $display);
        $this->assertStringContainsString(', ', $display);
    }
}
