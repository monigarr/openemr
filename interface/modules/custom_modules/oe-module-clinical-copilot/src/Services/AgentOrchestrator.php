<?php

/**
 * Tool → LLM → verification pipeline for inter-visit briefing.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use OpenEMR\Core\OEGlobalsBag;

final class AgentOrchestrator
{
    public function __construct(
        private readonly ChartContextTool $chartTool,
        private readonly OpenAiClient $openAi,
        private readonly VerificationGate $verification,
    ) {
    }

    /**
     * @return array{ok:bool,text?:string,error?:string,verified?:array,telemetry?:AgentTelemetry,usage?:array<string,int>,model?:string,estimated_usd?:float}
     */
    public function runBriefing(int $sessionPid, AgentTelemetry $telemetry, string $requestId): array
    {
        $telemetry->mark('start', true);
        if ($sessionPid < 1) {
            $telemetry->mark('pid_check', false, 'no active patient');
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'no_active_patient', 'telemetry' => $telemetry];
        }

        try {
            $toolData = $this->chartTool->collectForPatient($sessionPid);
            $telemetry->mark('tool_chart_context', true);
        } catch (\Throwable $e) {
            $telemetry->mark('tool_chart_context', false, $e->getMessage());
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'tool_failure', 'telemetry' => $telemetry];
        }

        $model = OEGlobalsBag::getInstance()->getString('clinical_copilot_openai_model') ?: 'gpt-4o-mini';
        if (!$this->openAi->hasApiKey()) {
            $telemetry->mark('openai', false, 'missing_api_key');
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'missing_openai_api_key', 'telemetry' => $telemetry];
        }

        $toolJson = json_encode($toolData, JSON_UNESCAPED_SLASHES);
        if ($toolJson === false) {
            $telemetry->mark('encode_tool', false);
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'encode_failure', 'telemetry' => $telemetry];
        }

        $system = <<<PROMPT
You are a clinical chart assistant for an authorized clinician viewing ONE patient in OpenEMR.
Use ONLY the JSON facts in CHART_JSON. Output strict JSON with this shape:
{"statements":[{"text":"string","citations":["dot.path.in.CHART_JSON"]}],"uncertainties":["string"]}
Rules:
- Every factual clinical statement MUST include one or more citations; each citation must be a valid path into CHART_JSON (e.g. patient.fname, allergies.0.title).
- If data is missing, put a short note in uncertainties; do not invent facts.
- Do not give dosing or new diagnoses.
- Keep the briefing brief (under 120 words across statements).
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "CHART_JSON:\n" . $toolJson . "\n\nTask: one-paragraph room briefing: who this is, why they may be here if inferable from problems only as possibilities labeled uncertain, allergies, key meds. Use citations."],
        ];

        try {
            $t0 = microtime(true);
            $resp = $this->openAi->chatJson($model, $messages);
            $telemetry->mark('openai', true, 'latency_ms=' . round((microtime(true) - $t0) * 1000));
        } catch (\Throwable $e) {
            $telemetry->mark('openai', false, $e->getMessage());
            $telemetry->flush($requestId, $sessionPid, []);
            return ['ok' => false, 'error' => 'openai_failure', 'telemetry' => $telemetry];
        }

        $parsed = json_decode($resp['content'], true);
        if (!is_array($parsed)) {
            $telemetry->mark('parse_model_json', false);
            $telemetry->flush($requestId, $sessionPid, $this->usageContext($resp));
            return ['ok' => false, 'error' => 'malformed_model_json', 'telemetry' => $telemetry];
        }
        $telemetry->mark('parse_model_json', true);

        $verified = $this->verification->verify($toolData, $parsed);
        $telemetry->mark('verification', true, 'kept=' . count($verified['statements']) . ',stripped=' . count($verified['stripped']));
        $text = $this->verification->formatForDisplay($verified);

        $usageCtx = $this->usageContext($resp);
        $telemetry->flush($requestId, $sessionPid, $usageCtx);

        return [
            'ok' => true,
            'text' => $text,
            'verified' => $verified,
            'telemetry' => $telemetry,
            'usage' => $resp['usage'],
            'model' => $resp['model'],
            'estimated_usd' => OpenAiClient::estimateCostUsd($resp['model'], $resp['usage']),
        ];
    }

    /**
     * @param array{usage:array<string,int>,model:string} $resp
     * @return array<string,mixed>
     */
    private function usageContext(array $resp): array
    {
        $u = $resp['usage'];
        return [
            'prompt_tokens' => $u['prompt_tokens'] ?? 0,
            'completion_tokens' => $u['completion_tokens'] ?? 0,
            'total_tokens' => $u['total_tokens'] ?? 0,
            'model' => $resp['model'],
            'estimated_usd' => OpenAiClient::estimateCostUsd($resp['model'], $u),
        ];
    }
}
