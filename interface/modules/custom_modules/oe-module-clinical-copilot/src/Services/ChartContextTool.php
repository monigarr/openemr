<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file ChartContextTool.php
 *
 * Collects bounded, server-side chart excerpts (demographics, lists) for model grounding.
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Call `collectForPatient($pid)` only after the controller confirms ACL and session binding for that pid.
 *
 * Usage example (integrator):
 * Extend with additional bounded queries; keep row caps and PHI minimization consistent with site policy.
 *
 * Security: Pass validated session `pid` only; never use unvalidated client input as `$pid`.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @subpackage Services
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md AgentOrchestrator.php VerificationGate.php
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use OpenEMR\Services\PatientService;

/**
 * Uses OpenEMR services + small bounded SQL for list rows.
 */
final class ChartContextTool
{
    private const MAX_LIST_ROWS = 25;

    /**
     * @return array<string,mixed>
     */
    public function collectForPatient(int $pid): array
    {
        if ($pid < 1) {
            return ['patient' => [], 'allergies' => [], 'medications' => [], 'problems' => [], 'note' => 'invalid_pid'];
        }

        $patientService = new PatientService();
        $row = $patientService->findByPid($pid);
        $patient = [
            'pid' => $pid,
            # Neutralize for HIIPAA Compliance
            # Remove completely or Cryptographic Hash and Salt
            # 'fname' => $row['fname'] ?? '',
            # 'lname' => $row['lname'] ?? '',
            # 'DOB' => $row['DOB'] ?? '',
            'sex' => $row['sex'] ?? '',
        ];

        return [
            'patient' => $patient,
            'allergies' => $this->fetchListTitles($pid, 'allergy'),
            'medications' => $this->fetchListTitles($pid, 'medication'),
            'problems' => $this->fetchListTitles($pid, 'medical_problem'),
        ];
    }

    /**
     * @return list<array{title:string,begdate?:string}>
     */
    private function fetchListTitles(int $pid, string $type): array
    {
        $sql = "SELECT title, begdate FROM lists WHERE pid = ? AND type = ? AND enddate IS NULL "
            . "ORDER BY date DESC LIMIT " . escape_limit(self::MAX_LIST_ROWS);
        $res = sqlStatement($sql, [$pid, $type]);
        $out = [];
        while ($row = sqlFetchArray($res)) {
            $t = trim((string)($row['title'] ?? ''));
            if ($t === '') {
                continue;
            }
            $out[] = ['title' => $t, 'begdate' => (string)($row['begdate'] ?? '')];
        }
        return $out;
    }
}
