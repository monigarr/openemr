<?php

/**
 * @version 0.1.0
 * @date 2026-05-03
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Isolated PHPUnit tests for `ControllerRouter::shouldSkipAdminAcl()` in Clinical Decision Rules (CDR):
 * documents which UI controllers enforce ACL internally versus those that rely on router-level admin checks.
 *
 * Usage: Run after changing CDR routing or ACL split between router and individual controllers.
 *
 * Example:
 *   php vendor/bin/phpunit -c phpunit-isolated.xml tests/Tests/Isolated/ClinicalDecisionRules/ControllerRouterAclTest.php
 *
 * Dependencies: `OpenEMR\ClinicalDecisionRules\Interface\ControllerRouter`, PHPUnit `TestCase`, `DataProvider` attribute.
 *
 * Security/PHI: No patient data; asserts routing ACL expectations only (misconfiguration could widen admin surfaces).
 * HIPAA: N/A — no PHI; production relevance is authorization correctness on CDR screens/API entry points.
 * FHIR: N/A — not interoperability.
 * Accessibility: N/A — non-UI test.
 * Performance: O(1) per controller name; trivial.
 * Stability: Data providers list canonical controller keys; update when new CDR controllers change ACL model.
 * Legal/compliance: OpenEMR GPLv3; ACL behavior must align with organizational access policies for clinical modules.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Tests\Isolated\ClinicalDecisionRules;

use OpenEMR\ClinicalDecisionRules\Interface\ControllerRouter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ControllerRouterAclTest extends TestCase
{
    /**
     * Controllers that handle their own ACL and skip the router-level admin check.
     *
     * @return array<string, array{string}>
     */
    public static function selfProtectingControllerProvider(): array
    {
        return [
            'review' => ['review'],
            'log' => ['log'],
        ];
    }

    /**
     * Controllers that require admin/super ACL at the router level.
     *
     * @return array<string, array{string}>
     */
    public static function adminProtectedControllerProvider(): array
    {
        return [
            'alerts' => ['alerts'],
            'ajax' => ['ajax'],
            'edit' => ['edit'],
            'add' => ['add'],
            'detail' => ['detail'],
            'browse' => ['browse'],
        ];
    }

    #[DataProvider('selfProtectingControllerProvider')]
    public function testShouldSkipAdminAclReturnsTrueForSelfProtectingControllers(string $controller): void
    {
        $router = new ControllerRouter();
        $this->assertTrue($router->shouldSkipAdminAcl($controller));
    }

    #[DataProvider('adminProtectedControllerProvider')]
    public function testShouldSkipAdminAclReturnsFalseForAdminProtectedControllers(string $controller): void
    {
        $router = new ControllerRouter();
        $this->assertFalse($router->shouldSkipAdminAcl($controller));
    }
}
