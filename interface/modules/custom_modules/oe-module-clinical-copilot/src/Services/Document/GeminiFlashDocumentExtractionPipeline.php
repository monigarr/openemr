<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Optional multimodal PDF extraction via Google Gemini (inline PDF, JSON mode).
 *
 * Usage: Set `CLINICAL_COPILOT_EXTRACTION_PIPELINE=gemini` and `CLINICAL_COPILOT_GEMINI_API_KEY`. Optional `CLINICAL_COPILOT_GEMINI_MODEL` (default `gemini-1.5-flash`).
 *
 * Security/PHI: PDF bytes sent to Google; use demo/synthetic data unless BAA covers this path.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class GeminiFlashDocumentExtractionPipeline implements DocumentExtractionPipelineInterface
{
    private const MAX_INLINE_BYTES = 4 * 1024 * 1024;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gemini-1.5-flash',
    ) {
    }

    /**
     * @param array{doc_type:string,path:string,original_filename:string} $pending
     * @return array{ok:bool,error?:string,lab_rows?:list<array<string,mixed>>,intake?:array<string,mixed>}
     */
    public function process(int $pid, array $pending): array
    {
        if ($pid < 1) {
            return ['ok' => false, 'error' => 'invalid_pid'];
        }
        $path = $pending['path'] ?? '';
        if (!is_string($path) || $path === '' || !is_file($path)) {
            return ['ok' => false, 'error' => 'missing_temp_file'];
        }
        $docType = $pending['doc_type'] ?? '';
        if (!is_string($docType) || ($docType !== 'lab_pdf' && $docType !== 'intake_form')) {
            return ['ok' => false, 'error' => 'invalid_doc_type'];
        }
        $size = filesize($path);
        if ($size === false || $size < 1) {
            return ['ok' => false, 'error' => 'empty_file'];
        }
        if ($size > self::MAX_INLINE_BYTES) {
            return ['ok' => false, 'error' => 'pdf_too_large_for_gemini_inline'];
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return ['ok' => false, 'error' => 'read_failed'];
        }
        $b64 = base64_encode($raw);
        $orig = $pending['original_filename'] ?? 'upload.pdf';
        $orig = is_string($orig) ? $orig : 'upload.pdf';
        $sourceId = 'gemini:' . hash('sha256', $orig . "\0" . (string) $size);

        $instruction = $docType === 'lab_pdf'
            ? <<<TXT
You are extracting structured data from a clinical lab PDF. Return a single JSON object only (no markdown) with this exact shape:
{"labs":[{"test_name":"string","value":"string","unit":"string","reference_range":"string","collection_date":"string (ISO or best effort)","abnormal_flag":boolean}]}
Rules: Use empty string for unknown text fields. abnormal_flag true if clearly marked high/low/H/L or critical. Do not invent tests not visible in the document.
TXT
            : <<<TXT
You are extracting structured data from a patient intake PDF/form. Return a single JSON object only (no markdown) with this exact shape:
{"intake":{"demographics":{},"chief_concern":"string","current_medications":["string"],"allergies":["string"],"family_history":["string"]}}
demographics should be short string key/value pairs seen on the form (no PHI beyond what is already in the document). Use empty array if a list is missing.
TXT;

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($this->model),
        );
        $body = [
            'contents' => [[
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => 'application/pdf',
                            'data' => $b64,
                        ],
                    ],
                    ['text' => $instruction],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ];
        $client = new Client(['timeout' => 120]);
        try {
            $response = $client->post($url, [
                'query' => ['key' => $this->apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $body,
            ]);
        } catch (GuzzleException $e) {
            return ['ok' => false, 'error' => 'gemini_http_error'];
        }
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return ['ok' => false, 'error' => 'gemini_bad_status'];
        }
        $decoded = json_decode((string) $response->getBody(), true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'gemini_invalid_response'];
        }
        $text = self::extractResponseText($decoded);
        if ($text === '') {
            return ['ok' => false, 'error' => 'gemini_empty_content'];
        }
        $json = json_decode($text, true);
        if (!is_array($json)) {
            return ['ok' => false, 'error' => 'gemini_non_json_payload'];
        }

        if ($docType === 'lab_pdf') {
            $labs = $json['labs'] ?? null;
            if (!is_array($labs) || $labs === []) {
                return ['ok' => false, 'error' => 'gemini_no_labs'];
            }
            $out = [];
            foreach ($labs as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $n = self::normalizeLabRow($row, $sourceId, $i);
                $tn = $n['test_name'] ?? '';
                $tv = $n['value'] ?? '';
                if (!is_string($tn) || !is_string($tv) || $tn === '' || $tv === '') {
                    continue;
                }
                $out[] = $n;
            }
            if ($out === []) {
                return ['ok' => false, 'error' => 'gemini_lab_parse_failed'];
            }
            return ['ok' => true, 'lab_rows' => $out];
        }

        $intake = $json['intake'] ?? null;
        if (!is_array($intake)) {
            return ['ok' => false, 'error' => 'gemini_no_intake'];
        }
        $normalized = self::normalizeIntake($intake, $sourceId);
        return ['ok' => true, 'intake' => $normalized];
    }

    /**
     * @param array<string,mixed> $decoded
     */
    private static function extractResponseText(array $decoded): string
    {
        $candidates = $decoded['candidates'] ?? null;
        if (!is_array($candidates) || $candidates === []) {
            return '';
        }
        $first = $candidates[0];
        if (!is_array($first)) {
            return '';
        }
        $content = $first['content'] ?? null;
        if (!is_array($content)) {
            return '';
        }
        $parts = $content['parts'] ?? null;
        if (!is_array($parts)) {
            return '';
        }
        $chunks = [];
        foreach ($parts as $p) {
            if (!is_array($p)) {
                continue;
            }
            $t = $p['text'] ?? '';
            if (is_string($t) && $t !== '') {
                $chunks[] = $t;
            }
        }
        return trim(implode('', $chunks));
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function normalizeLabRow(array $row, string $sourceId, int $index): array
    {
        $name = isset($row['test_name']) && is_string($row['test_name']) ? trim($row['test_name']) : '';
        $value = isset($row['value']) && is_string($row['value']) ? trim($row['value']) : '';
        $unit = isset($row['unit']) && is_string($row['unit']) ? trim($row['unit']) : '';
        $ref = isset($row['reference_range']) && is_string($row['reference_range']) ? trim($row['reference_range']) : '';
        $date = isset($row['collection_date']) && is_string($row['collection_date']) ? trim($row['collection_date']) : '';
        $ab = $row['abnormal_flag'] ?? false;
        $abnormal = is_bool($ab) ? $ab : false;
        $fieldId = $name !== '' ? strtolower(preg_replace('/\s+/', '_', $name) ?? 'lab_' . $index) : 'lab_' . $index;
        return [
            'test_name' => $name,
            'value' => $value,
            'unit' => $unit,
            'reference_range' => $ref,
            'collection_date' => $date,
            'abnormal_flag' => $abnormal,
            'citation' => [
                'source_type' => 'lab_pdf',
                'source_id' => $sourceId,
                'page_or_section' => 'model_extracted',
                'field_or_chunk_id' => $fieldId,
                'quote_or_value' => $name !== '' && $value !== '' ? ($name . ' ' . $value . ' ' . $unit) : $value,
                'bbox_norm' => ['x' => 0.05, 'y' => min(0.85, 0.08 + $index * 0.06), 'w' => 0.9, 'h' => 0.05],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $intake
     * @return array<string,mixed>
     */
    private static function normalizeIntake(array $intake, string $sourceId): array
    {
        $demo = $intake['demographics'] ?? [];
        if (!is_array($demo)) {
            $demo = [];
        }
        $chief = isset($intake['chief_concern']) && is_string($intake['chief_concern']) ? trim($intake['chief_concern']) : '';
        $meds = $intake['current_medications'] ?? [];
        $all = $intake['allergies'] ?? [];
        $fh = $intake['family_history'] ?? [];
        $meds = is_array($meds) ? array_values(array_filter($meds, static fn ($x) => is_string($x) && $x !== '')) : [];
        $all = is_array($all) ? array_values(array_filter($all, static fn ($x) => is_string($x) && $x !== '')) : [];
        $fh = is_array($fh) ? array_values(array_filter($fh, static fn ($x) => is_string($x) && $x !== '')) : [];
        $demoOut = [];
        foreach ($demo as $k => $v) {
            if (is_string($k) && is_string($v) && $k !== '' && $v !== '') {
                $demoOut[$k] = $v;
            }
        }
        return [
            'demographics' => $demoOut,
            'chief_concern' => $chief,
            'current_medications' => $meds,
            'allergies' => $all,
            'family_history' => $fh,
            'citation' => [
                'source_type' => 'intake_form',
                'source_id' => $sourceId,
                'page_or_section' => 'model_extracted',
                'field_or_chunk_id' => 'intake_summary',
                'quote_or_value' => $chief !== '' ? $chief : 'Intake extraction',
                'bbox_norm' => ['x' => 0.08, 'y' => 0.1, 'w' => 0.84, 'h' => 0.25],
            ],
        ];
    }
}
