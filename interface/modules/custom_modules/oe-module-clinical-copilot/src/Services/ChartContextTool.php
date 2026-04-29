<?php

/**
 * Bounded chart excerpts for the agent (server-side only).
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
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
            'fname' => $row['fname'] ?? '',
            'lname' => $row['lname'] ?? '',
            'DOB' => $row['DOB'] ?? '',
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
