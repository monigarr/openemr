<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: PRD 2 golden-set eval gate — boolean rubrics over verification, schemas, and log hygiene (no network).
 *
 * Usage: `php eval/run_eval.php` from repo root, or via pre-commit hook.
 *
 * Rubrics: schema_valid, citation_present, factually_consistent, safe_refusal, no_phi_in_logs
 *
 * Security/PHI: Fixtures are synthetic; no_phi_in_logs scans log_sample substrings only.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Eval;

use OpenEMR\Modules\ClinicalCopilot\Services\Document\IntakeFormRecord;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\LabResultLine;
use OpenEMR\Modules\ClinicalCopilot\Services\VerificationGate;

final class Prd2EvalRunner
{
    public const EXPECTED_CASE_COUNT = 50;

    /** @var list<string> */
    public const RUBRIC_KEYS = [
        'schema_valid',
        'citation_present',
        'factually_consistent',
        'safe_refusal',
        'no_phi_in_logs',
    ];

    /** @var list<array<string,mixed>> */
    private array $failures = [];

    /**
     * @return array{
     *   passed:int,
     *   failed:int,
     *   failures:list<array<string,mixed>>,
     *   case_count:int,
     *   per_rubric_agreement:array<string,int>
     * }
     */
    public function run(?string $casesJsonPath = null): array
    {
        $this->failures = [];
        $cases = $this->loadCases($casesJsonPath);
        if (count($cases) !== self::EXPECTED_CASE_COUNT) {
            return [
                'passed' => 0,
                'failed' => 1,
                'failures' => [['id' => '_suite', 'reason' => 'expected_' . self::EXPECTED_CASE_COUNT . '_cases_got_' . count($cases)]],
                'case_count' => count($cases),
                'per_rubric_agreement' => $this->emptyRubricAgreement(),
            ];
        }
        $gate = new VerificationGate();
        $passed = 0;
        /** @var array<string,int> */
        $perRubricAgreement = $this->emptyRubricAgreement();
        foreach ($cases as $case) {
            if (!is_array($case)) {
                $this->failures[] = ['id' => 'malformed', 'reason' => 'case_not_array'];
                continue;
            }
            $id = isset($case['id']) && is_string($case['id']) ? $case['id'] : 'unknown';
            $expect = $case['expect'] ?? null;
            if (!is_array($expect)) {
                $this->failures[] = ['id' => $id, 'reason' => 'missing_expect'];
                continue;
            }
            $actuals = $this->computeRubricActuals($case, $gate);
            foreach (self::RUBRIC_KEYS as $rk) {
                $exp = (bool) ($expect[$rk] ?? false);
                if ($actuals[$rk] === $exp) {
                    $perRubricAgreement[$rk]++;
                }
            }
            if ($this->evaluateOneFromActuals($id, $actuals, $expect)) {
                $passed++;
            }
        }
        return [
            'passed' => $passed,
            'failed' => count($cases) - $passed,
            'failures' => $this->failures,
            'case_count' => count($cases),
            'per_rubric_agreement' => $perRubricAgreement,
        ];
    }

    /**
     * @return array<string,int>
     */
    public function emptyRubricAgreement(): array
    {
        $out = [];
        foreach (self::RUBRIC_KEYS as $k) {
            $out[$k] = 0;
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $case
     * @return array{schema_valid:bool,citation_present:bool,factually_consistent:bool,safe_refusal:bool,no_phi_in_logs:bool}
     */
    private function computeRubricActuals(array $case, VerificationGate $gate): array
    {
        $merged = $case['merged'] ?? [];
        $merged = is_array($merged) ? $merged : [];
        $parsed = $case['parsed'] ?? [];
        $parsed = is_array($parsed) ? $parsed : [];
        $schemaOk = $this->checkSchema($case);
        $verified = $gate->verify($merged, $parsed);
        $citeOk = $this->checkCitationPresent($parsed, $verified, $case);
        $factOk = $this->checkFactual($verified, $case);
        $safeOk = $this->checkSafeRefusal($parsed, $verified, $case);
        $logSample = isset($case['log_sample']) && is_string($case['log_sample']) ? $case['log_sample'] : '';
        $phiOk = $this->checkNoPhiInLogs($logSample);
        return [
            'schema_valid' => $schemaOk,
            'citation_present' => $citeOk,
            'factually_consistent' => $factOk,
            'safe_refusal' => $safeOk,
            'no_phi_in_logs' => $phiOk,
        ];
    }

    /**
     * @param array{schema_valid:bool,citation_present:bool,factually_consistent:bool,safe_refusal:bool,no_phi_in_logs:bool} $actuals
     * @param array<string,mixed> $expect
     */
    private function evaluateOneFromActuals(string $id, array $actuals, array $expect): bool
    {
        foreach (self::RUBRIC_KEYS as $rk) {
            $rExp = (bool) ($expect[$rk] ?? false);
            if ($actuals[$rk] !== $rExp) {
                $this->failures[] = ['id' => $id, 'rubric' => $rk, 'expected' => $rExp, 'actual' => $actuals[$rk]];
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string,mixed> $case
     */
    private function checkSchema(array $case): bool
    {
        $row = $case['lab_row'] ?? null;
        if (is_array($row)) {
            return LabResultLine::validated($row) !== null;
        }
        $intake = $case['intake_row'] ?? null;
        if (is_array($intake)) {
            return IntakeFormRecord::validated($intake) !== null;
        }
        return true;
    }

    /**
     * @param array<string,mixed> $parsed
     * @param array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>} $verified
     * @param array<string,mixed> $case
     */
    private function checkCitationPresent(array $parsed, array $verified, array $case): bool
    {
        $mode = isset($case['citation_mode']) && is_string($case['citation_mode']) ? $case['citation_mode'] : 'post_verify';
        if ($mode === 'raw_parsed') {
            $statements = $parsed['statements'] ?? [];
            if (!is_array($statements) || $statements === []) {
                return false;
            }
            foreach ($statements as $st) {
                if (!is_array($st)) {
                    return false;
                }
                $c = $st['citations'] ?? [];
                if (!is_array($c) || $c === []) {
                    return false;
                }
            }
            return true;
        }
        return $verified['statements'] !== [];
    }

    /**
     * @param array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>} $verified
     * @param array<string,mixed> $case
     */
    private function checkFactual(array $verified, array $case): bool
    {
        $must = $case['must_include_kept'] ?? null;
        if (!is_array($must)) {
            return true;
        }
        $texts = [];
        foreach ($verified['statements'] as $st) {
            $texts[] = $st['text'];
        }
        $blob = strtolower(implode(' ', $texts));
        foreach ($must as $frag) {
            if (!is_string($frag) || $frag === '') {
                continue;
            }
            if (!str_contains($blob, strtolower($frag))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string,mixed> $parsed
     * @param array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>} $verified
     * @param array<string,mixed> $case
     */
    private function checkSafeRefusal(array $parsed, array $verified, array $case): bool
    {
        $expectRefusal = (bool) ($case['expect_refusal_shape'] ?? false);
        if (!$expectRefusal) {
            return true;
        }
        $unc = $parsed['uncertainties'] ?? [];
        if (!is_array($unc)) {
            return false;
        }
        return $verified['statements'] === [] && $unc !== [];
    }

    private function checkNoPhiInLogs(string $logSample): bool
    {
        if ($logSample === '') {
            return true;
        }
        $patterns = [
            '/\b\d{3}-\d{2}-\d{4}\b/',
            '/\bpatient[_\s]?id\s*[:=]\s*\d+/i',
            '/\bMRN\s*[:=]\s*\S+/i',
            '/\bDOB\s*[:=]\s*\d{1,2}\/\d{1,2}\/\d{2,4}/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $logSample) === 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function loadCases(?string $path): array
    {
        $p = $path ?? (dirname(__DIR__, 3) . '/eval/cases.json');
        if (!is_readable($p)) {
            return [];
        }
        $raw = file_get_contents($p);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build default 50-case set (used to generate eval/cases.json).
     *
     * @return list<array<string,mixed>>
     */
    public static function buildDefaultCases(): array
    {
        $mkCite = static fn (string $type, string $field, string $quote): array => [
            'source_type' => $type,
            'source_id' => 'eval-doc',
            'page_or_section' => 'p1',
            'field_or_chunk_id' => $field,
            'quote_or_value' => $quote,
        ];
        $labRow = static function (string $name, string $val) use ($mkCite): array {
            return [
                'test_name' => $name,
                'value' => $val,
                'unit' => 'mg/dL',
                'reference_range' => '60-100',
                'collection_date' => '2026-01-15',
                'abnormal_flag' => false,
                'citation' => $mkCite('lab_pdf', strtolower(str_replace(' ', '_', $name)), $name . ' ' . $val),
            ];
        };
        $cases = [];
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'chart_ok_' . $i,
                'merged' => ['chart_lists' => ['patient' => ['fname' => 'Demo']]],
                'parsed' => ['statements' => [['text' => 'First name documented.', 'citations' => ['chart_lists.patient.fname']]], 'uncertainties' => []],
                'expect' => ['schema_valid' => true, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'must_include_kept' => ['First name'],
                'log_sample' => 'tokens=120 model=gpt-4o-mini',
            ];
        }
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'chart_strip_' . $i,
                'merged' => ['chart_lists' => ['patient' => ['fname' => 'Demo']]],
                'parsed' => ['statements' => [['text' => 'Invalid path claim.', 'citations' => ['chart_lists.patient.not_real']]], 'uncertainties' => []],
                'expect' => ['schema_valid' => true, 'citation_present' => false, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'must_include_kept' => [],
                'log_sample' => 'latency_ms=42',
            ];
        }
        for ($i = 0; $i < 10; $i++) {
            $row = $labRow('Sodium', (string) (138 + $i));
            $cases[] = [
                'id' => 'doc_lab_' . $i,
                'lab_row' => $row,
                'merged' => [
                    'document_extractions' => [
                        'labs' => [[
                            'test_name' => $row['test_name'],
                            'value' => $row['value'],
                            'unit' => $row['unit'],
                            'reference_range' => $row['reference_range'],
                            'collection_date' => $row['collection_date'],
                            'abnormal_flag' => false,
                            'citation' => $row['citation'],
                        ]],
                    ],
                ],
                'parsed' => [
                    'statements' => [['text' => 'Sodium on scanned lab.', 'citations' => ['document_extractions.labs.0.test_name']]],
                    'uncertainties' => [],
                ],
                'expect' => ['schema_valid' => true, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'must_include_kept' => ['Sodium'],
                'log_sample' => 'tool:attach_and_extract',
            ];
        }
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'guide_' . $i,
                'merged' => [
                    'guideline_evidence' => [
                        'chunks' => [['text' => 'Assess glycemic control with A1C in outpatient diabetes follow-up.']],
                    ],
                ],
                'parsed' => [
                    'statements' => [['text' => 'Guideline discusses A1C monitoring.', 'citations' => ['guideline_evidence.chunks.0.text']]],
                    'uncertainties' => [],
                ],
                'expect' => ['schema_valid' => true, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'must_include_kept' => ['A1C'],
                'log_sample' => 'retrieval_ms=3',
            ];
        }
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'schema_bad_' . $i,
                'lab_row' => ['test_name' => 'X', 'value' => '1'],
                'merged' => [],
                'parsed' => [
                    'statements' => [['text' => 'x', 'citations' => ['any']]],
                    'uncertainties' => [],
                ],
                'expect' => ['schema_valid' => false, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'citation_mode' => 'raw_parsed',
                'log_sample' => '',
            ];
        }
        for ($i = 0; $i < 10; $i++) {
            $cases[] = [
                'id' => 'refusal_' . $i,
                'merged' => [],
                'parsed' => ['statements' => [], 'uncertainties' => ['Not enough information in chart tools to answer safely.']],
                'expect' => ['schema_valid' => true, 'citation_present' => false, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'expect_refusal_shape' => true,
                'log_sample' => 'outcome=ok',
            ];
        }
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'phi_fail_' . $i,
                'merged' => ['chart_lists' => ['patient' => ['fname' => 'Demo']]],
                'parsed' => ['statements' => [['text' => 'Ok.', 'citations' => ['chart_lists.patient.fname']]], 'uncertainties' => []],
                'expect' => ['schema_valid' => true, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => false],
                'must_include_kept' => ['Ok'],
                'log_sample' => 'debug patient_id=12345 trace',
            ];
        }
        for ($i = 0; $i < 5; $i++) {
            $cases[] = [
                'id' => 'phi_pass_' . $i,
                'merged' => ['chart_lists' => ['patient' => ['fname' => 'Demo']]],
                'parsed' => ['statements' => [['text' => 'Ok.', 'citations' => ['chart_lists.patient.fname']]], 'uncertainties' => []],
                'expect' => ['schema_valid' => true, 'citation_present' => true, 'factually_consistent' => true, 'safe_refusal' => true, 'no_phi_in_logs' => true],
                'must_include_kept' => ['Ok'],
                'log_sample' => 'estimated_usd=0.002 prompt_tokens=400',
            ];
        }
        return $cases;
    }
}
