<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file AgentOrchestrator.php
 *
 * Tool registry → OpenAI (tool loop + fallback) → verification → domain rules → UI payload.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ToolRegistry;

final class AgentOrchestrator
{
    private const MAX_TOOL_ROUNDS = 5;

    /**
     * Maps OpenAI tool names to PRD Week 2 worker roles for inspectable supervisor handoffs.
     *
     * @var array<string,string>
     */
    private const TOOL_TO_WORKER = [
        'attach_and_extract' => 'intake_extractor',
        'get_document_extractions' => 'intake_extractor',
        'retrieve_guidelines' => 'evidence_retriever',
        'get_chart_lists' => 'chart_context',
        'get_recent_encounters' => 'chart_context',
        'get_recent_labs' => 'chart_context',
    ];

    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly OpenAiClient $openAi,
        private readonly VerificationGate $verification,
        private readonly ClinicalDomainRules $domainRules,
        private readonly CitationLabelBuilder $citationLabels,
        private readonly DisplaySanitizer $displaySanitizer,
    ) {
    }

    /**
     * Backward-compatible entry: optional prior chat and user line (defaults to inter-visit briefing).
     *
     * @param list<array{role:string,content:string}> $priorChat
     * @param CopilotRunInstrumentation|null $instrumentation Optional Langfuse + trace context (controller-supplied).
     * @return array{
     *   ok:bool,text?:string,error?:string,verified?:array,domain?:array,statements_for_ui?:list<array{text:string,citations:list<array{path:string,label:string}>}>,
     *   telemetry?:AgentTelemetry,usage?:array<string,int>,model?:string,estimated_usd?:float,fallback_premerged?:bool,
     *   extraction_overlays?:list<array{path:string,label:string,bbox_norm:array{x:float,y:float,w:float,h:float}}>,
     *   supervisor_handoffs?:list<array{round:int,tool:string,worker:string,handoff:string,latency_ms:float}>
     * }
     */
    public function runBriefing(int $sessionPid, AgentTelemetry $telemetry, string $requestId, array $priorChat = [], string $userLine = '', ?CopilotRunInstrumentation $instrumentation = null): array
    {
        $userLine = trim($userLine);
        if ($userLine === '') {
            $userLine = 'Provide a concise inter-visit briefing: visit framing from problems and encounters only as possibilities (label uncertainty), allergies, medications, and notable labs if present. If today\'s visit reason is not documented, say so in uncertainties. Do not repeat patient name or DOB in statement text.';
        }
        return $this->runAgentTurn($sessionPid, $telemetry, $requestId, $priorChat, $userLine, $instrumentation);
    }

    /**
     * @param list<array{role:string,content:string}> $priorChat
     * @param CopilotRunInstrumentation|null $instrumentation Optional Langfuse + trace context (controller-supplied).
     * @return array{
     *   ok:bool,text?:string,error?:string,verified?:array,domain?:array,statements_for_ui?:list<array{text:string,citations:list<array{path:string,label:string}>}>,
     *   telemetry?:AgentTelemetry,usage?:array<string,int>,model?:string,estimated_usd?:float,fallback_premerged?:bool,
     *   extraction_overlays?:list<array{path:string,label:string,bbox_norm:array{x:float,y:float,w:float,h:float}}>,
     *   supervisor_handoffs?:list<array{round:int,tool:string,worker:string,handoff:string,latency_ms:float}>
     * }
     */
    public function runAgentTurn(int $sessionPid, AgentTelemetry $telemetry, string $requestId, array $priorChat, string $userLine, ?CopilotRunInstrumentation $instrumentation = null): array
    {
        $obs = $instrumentation?->observability ?? new NullCopilotObservability();
        $traceCtx = $instrumentation?->traceContext;

        $telemetry->mark('start', true);
        if ($traceCtx !== null) {
            $obs->beginTrace($requestId, $traceCtx);
        }

        if ($sessionPid < 1) {
            $telemetry->mark('pid_check', false, 'no active patient');
            $obs->finalizeTrace(['outcome' => 'error', 'error' => 'no_active_patient']);
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'no_active_patient', 'telemetry' => $telemetry];
        }

        if (!$this->openAi->hasApiKey()) {
            $telemetry->mark('openai', false, 'missing_api_key');
            $obs->finalizeTrace(['outcome' => 'error', 'error' => 'missing_openai_api_key']);
            $telemetry->flush($requestId, $sessionPid);
            return ['ok' => false, 'error' => 'missing_openai_api_key', 'telemetry' => $telemetry];
        }

        $model = OEGlobalsBag::getInstance()->getString('clinical_copilot_openai_model') ?: 'gpt-4o-mini';

        $tCollect0 = microtime(true);
        $merged = $this->toolRegistry->collectMergedBase($sessionPid);
        $obs->recordSpan('tools_collect_merged_base', $tCollect0, microtime(true), true, null, []);
        $telemetry->mark('tools_collect_merged_base', true);

        $system = $this->buildSystemPrompt();

        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($this->trimChat($priorChat) as $row) {
            $role = $row['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $row['content']];
        }
        $messages[] = ['role' => 'user', 'content' => "Clinician request:\n" . $userLine];

        $fallback = false;
        $mergedUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        $modelOut = $model;
        $finalContent = '';
        /** @var array<string,mixed> */
        $mergedDynamic = [];
        /** @var list<array{round:int,tool:string,worker:string,handoff:string,latency_ms:float}> */
        $supervisorHandoffs = [];

        try {
            $roundMessages = $messages;
            for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
                $t0 = microtime(true);
                $inputPreview = $this->messagesPreviewForObs($roundMessages);
                $resp = $this->openAi->chatCompletionRound(
                    $model,
                    $roundMessages,
                    $this->toolRegistry->openAiToolsArray(),
                    null
                );
                $t1 = microtime(true);
                $telemetry->mark('openai_round', true, 'round=' . $round . ',ms=' . round(($t1 - $t0) * 1000));
                $mergedUsage = OpenAiClient::mergeUsageTokens($mergedUsage, $resp['usage']);
                $modelOut = $resp['model'];
                $assistantMsg = $resp['message'];
                $outputPreview = $this->assistantMessagePreviewForObs($assistantMsg);
                $obs->recordOpenAiGeneration(
                    'openai_tool_loop_round_' . $round,
                    $modelOut,
                    $t0,
                    $t1,
                    $resp['usage'],
                    $inputPreview,
                    $outputPreview,
                );
                $roundMessages[] = $assistantMsg;

                $toolCalls = $assistantMsg['tool_calls'] ?? null;
                if (is_array($toolCalls) && $toolCalls !== []) {
                    foreach ($toolCalls as $tc) {
                        if (!is_array($tc)) {
                            continue;
                        }
                        $id = isset($tc['id']) && is_string($tc['id']) ? $tc['id'] : '';
                        $fn = $tc['function'] ?? null;
                        $name = '';
                        if (is_array($fn) && isset($fn['name']) && is_string($fn['name'])) {
                            $name = $fn['name'];
                        }
                        if ($id === '' || $name === '') {
                            continue;
                        }
                        $argsJson = null;
                        if (is_array($fn) && array_key_exists('arguments', $fn)) {
                            $argRaw = $fn['arguments'];
                            if (is_string($argRaw) && $argRaw !== '') {
                                $argsJson = $argRaw;
                            }
                        }
                        $tTool = microtime(true);
                        $payload = $this->toolRegistry->runTool($name, $sessionPid, $argsJson);
                        $tToolEnd = microtime(true);
                        $worker = self::TOOL_TO_WORKER[$name] ?? 'chart_context';
                        $supervisorHandoffs[] = [
                            'round' => $round,
                            'tool' => $name,
                            'worker' => $worker,
                            'handoff' => 'supervisor_to_' . $worker,
                            'latency_ms' => round(($tToolEnd - $tTool) * 1000, 3),
                        ];
                        $telemetry->mark('tool:' . $name, true, 'ms=' . round(($tToolEnd - $tTool) * 1000));
                        $obs->recordSpan(
                            'tool:' . $name,
                            $tTool,
                            $tToolEnd,
                            true,
                            'chars=' . strlen($payload),
                            ['tool' => $name],
                        );
                        $roundMessages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $id,
                            'content' => $payload,
                        ];
                        $mergeKey = $this->toolRegistry->mergeKeyFor($name);
                        if ($mergeKey !== null && $mergeKey !== '') {
                            $decodedTool = json_decode($payload, true);
                            if (is_array($decodedTool)) {
                                $mergedDynamic[$mergeKey] = $decodedTool;
                            }
                        }
                    }
                    continue;
                }

                $content = $assistantMsg['content'] ?? '';
                $finalContent = is_string($content) ? trim($content) : '';
                if ($finalContent !== '') {
                    break;
                }
            }

            if ($finalContent === '') {
                $telemetry->mark('openai_tool_loop_empty', false);
                $ts = microtime(true);
                $obs->recordSpan('openai_tool_loop_empty', $ts, $ts, false, 'no assistant content after rounds', []);
                $fallback = true;
            }
        } catch (\Throwable $e) {
            $telemetry->mark('openai', false, $e->getMessage());
            $ts = microtime(true);
            $obs->recordSpan('openai_exception', $ts, $ts, false, $e->getMessage(), []);
            $fallback = true;
        }

        if ($fallback) {
            $telemetry->mark('fallback_premerged', true);
            $merged = $this->toolRegistry->collectMergedBase($sessionPid);
            $toolJson = json_encode($merged, JSON_UNESCAPED_SLASHES);
            if ($toolJson === false) {
                $telemetry->mark('encode_tool', false);
                $obs->finalizeTrace(['outcome' => 'error', 'error' => 'encode_failure', 'fallback_premerged' => true]);
                $telemetry->flush($requestId, $sessionPid, $mergedUsage);
                return ['ok' => false, 'error' => 'encode_failure', 'telemetry' => $telemetry];
            }
            $fbMessages = [['role' => 'system', 'content' => $system]];
            foreach (array_slice($this->trimChat($priorChat), -8) as $row) {
                $fbMessages[] = [
                    'role' => $row['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $row['content'],
                ];
            }
            $fbMessages[] = ['role' => 'user', 'content' => "TOOL_BUNDLE_JSON:\n" . $toolJson . "\n\nClinician request:\n" . $userLine];
            try {
                $t0 = microtime(true);
                $fbInput = $this->messagesPreviewForObs($fbMessages);
                $resp = $this->openAi->chatJson($model, $fbMessages);
                $t1 = microtime(true);
                $telemetry->mark('openai_fallback', true, 'latency_ms=' . round(($t1 - $t0) * 1000));
                $mergedUsage = OpenAiClient::mergeUsageTokens($mergedUsage, $resp['usage']);
                $modelOut = $resp['model'];
                $finalContent = trim($resp['content']);
                $obs->recordOpenAiGeneration(
                    'openai_fallback_json',
                    $modelOut,
                    $t0,
                    $t1,
                    $resp['usage'],
                    $fbInput,
                    TelemetryText::clipForLog($finalContent, 4000),
                );
            } catch (\Throwable $e) {
                $telemetry->mark('openai_fallback', false, $e->getMessage());
                $obs->finalizeTrace([
                    'outcome' => 'error',
                    'error' => 'openai_failure',
                    'fallback_premerged' => true,
                    'model' => $modelOut,
                ]);
                $telemetry->flush($requestId, $sessionPid, $this->usageCtx($mergedUsage, $modelOut));
                return ['ok' => false, 'error' => 'openai_failure', 'telemetry' => $telemetry];
            }
        }

        $merged = array_merge($this->toolRegistry->collectMergedBase($sessionPid), $mergedDynamic);

        $parsed = json_decode($finalContent, true);
        if (!is_array($parsed)) {
            $telemetry->mark('parse_model_json', false);
            $obs->finalizeTrace([
                'outcome' => 'error',
                'error' => 'malformed_model_json',
                'fallback_premerged' => $fallback,
                'model' => $modelOut,
            ]);
            $telemetry->flush($requestId, $sessionPid, $this->usageCtx($mergedUsage, $modelOut));
            return ['ok' => false, 'error' => 'malformed_model_json', 'telemetry' => $telemetry];
        }
        $telemetry->mark('parse_model_json', true);

        $tVer0 = microtime(true);
        $verified = $this->verification->verify($merged, $parsed);
        $obs->recordSpan(
            'verification',
            $tVer0,
            microtime(true),
            true,
            'kept=' . count($verified['statements']) . ',stripped=' . count($verified['stripped']),
            [],
        );
        $telemetry->mark('verification', true, 'kept=' . count($verified['statements']) . ',stripped=' . count($verified['stripped']));

        $tDom0 = microtime(true);
        $domain = $this->domainRules->apply($verified, $merged);
        $obs->recordSpan(
            'domain_rules',
            $tDom0,
            microtime(true),
            true,
            'domain_stripped=' . count($domain['domain_stripped']),
            [],
        );
        $telemetry->mark('domain_rules', true, 'domain_stripped=' . count($domain['domain_stripped']));

        $displaySlice = [
            'statements' => $domain['statements'],
            'uncertainties' => $domain['uncertainties'],
            'stripped' => $verified['stripped'],
        ];
        $text = $this->verification->formatForDisplay($displaySlice);
        if ($domain['domain_stripped'] !== []) {
            $text .= "\n\n[Clinical safety filter: " . count($domain['domain_stripped']) . " statement(s) withheld.]";
        }

        $statementsForUi = [];
        foreach ($domain['statements'] as $st) {
            $cleanText = $this->displaySanitizer->sanitizeLine($st['text']);
            $statementsForUi[] = [
                'text' => $cleanText,
                'citations' => $this->citationLabels->labelsForPaths($merged, $st['citations']),
            ];
        }

        $usageCtx = $this->usageCtx($mergedUsage, $modelOut);
        $telemetry->flush($requestId, $sessionPid, $usageCtx);

        $obs->finalizeTrace([
            'outcome' => 'ok',
            'fallback_premerged' => $fallback,
            'model' => $modelOut,
            'estimated_usd' => OpenAiClient::estimateCostUsd($modelOut, $mergedUsage),
            'prompt_tokens' => $mergedUsage['prompt_tokens'] ?? 0,
            'completion_tokens' => $mergedUsage['completion_tokens'] ?? 0,
            'total_tokens' => $mergedUsage['total_tokens'] ?? 0,
            'statements_for_ui_count' => count($statementsForUi),
            'stripped_count' => count($verified['stripped']),
            'domain_stripped_count' => count($domain['domain_stripped']),
            'supervisor_handoff_count' => count($supervisorHandoffs),
        ]);

        return [
            'ok' => true,
            'text' => $text,
            'verified' => $verified,
            'domain' => $domain,
            'statements_for_ui' => $statementsForUi,
            'extraction_overlays' => $this->buildExtractionOverlays($merged),
            'supervisor_handoffs' => $supervisorHandoffs,
            'telemetry' => $telemetry,
            'usage' => $mergedUsage,
            'model' => $modelOut,
            'estimated_usd' => OpenAiClient::estimateCostUsd($modelOut, $mergedUsage),
            'fallback_premerged' => $fallback,
        ];
    }

    /**
     * Normalized PDF bounding boxes for UI overlay (0–1 coordinates).
     *
     * @param array<string,mixed> $merged
     * @return list<array{path:string,label:string,bbox_norm:array{x:float,y:float,w:float,h:float}}>
     */
    private function buildExtractionOverlays(array $merged): array
    {
        $out = [];
        $de = $merged['document_extractions'] ?? null;
        if (!is_array($de)) {
            return $out;
        }
        $labs = $de['labs'] ?? [];
        if (is_array($labs)) {
            foreach ($labs as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $cite = $row['citation'] ?? null;
                $bbox = is_array($cite) ? ($cite['bbox_norm'] ?? null) : null;
                $tn = isset($row['test_name']) && is_string($row['test_name']) ? $row['test_name'] : '';
                if (!is_array($bbox) || $tn === '') {
                    continue;
                }
                if (!isset($bbox['x'], $bbox['y'], $bbox['w'], $bbox['h'])) {
                    continue;
                }
                $out[] = [
                    'path' => 'document_extractions.labs.' . $i . '.test_name',
                    'label' => $tn,
                    'bbox_norm' => [
                        'x' => (float) $bbox['x'],
                        'y' => (float) $bbox['y'],
                        'w' => (float) $bbox['w'],
                        'h' => (float) $bbox['h'],
                    ],
                ];
            }
        }
        $intakes = $de['intakes'] ?? [];
        if (is_array($intakes)) {
            foreach ($intakes as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $cite = $row['citation'] ?? null;
                $bbox = is_array($cite) ? ($cite['bbox_norm'] ?? null) : null;
                $chief = isset($row['chief_concern']) && is_string($row['chief_concern']) ? $row['chief_concern'] : '';
                if (!is_array($bbox) || $chief === '') {
                    continue;
                }
                if (!isset($bbox['x'], $bbox['y'], $bbox['w'], $bbox['h'])) {
                    continue;
                }
                $out[] = [
                    'path' => 'document_extractions.intakes.' . $i . '.chief_concern',
                    'label' => $chief,
                    'bbox_norm' => [
                        'x' => (float) $bbox['x'],
                        'y' => (float) $bbox['y'],
                        'w' => (float) $bbox['w'],
                        'h' => (float) $bbox['h'],
                    ],
                ];
            }
        }
        return $out;
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are a clinical chart assistant for an authorized clinician viewing ONE patient in OpenEMR.
You may call the provided tools to load chart facts, uploaded document extractions, and guideline excerpts. Use ONLY data returned by tools (and uncertainties when data is missing).
When a clinician has uploaded a lab PDF or intake form, call attach_and_extract with the matching doc_type before citing extracted facts, then call get_document_extractions if needed for refreshed rows.
Separate patient-specific facts (chart_lists, recent_encounters, recent_labs, document_extractions) from general guideline text (guideline_evidence). Never present guideline text as patient-specific results.
When you have enough context, respond with a single JSON object (no markdown) exactly in this shape:
{"statements":[{"text":"string","citations":["dot.path.in.TOOL_BUNDLE"]}],"uncertainties":["string"]}
Citation rules:
- Paths must resolve under merged tool roots: chart_lists.*, recent_encounters.*, recent_labs.*, document_extractions.*, guideline_evidence.* (e.g. document_extractions.labs.0.test_name, document_extractions.provenance.chart_document_id, guideline_evidence.chunks.0.text).
- Every factual clinical statement MUST include one or more valid citations.
- Do not include patient name, initials, DOB, MRN, address, or phone in statement text (the chart header already shows identifiers).
- If something is unknown or not in the chart, add a short note to uncertainties — never invent facts.
- Do not give dosing, medication start/stop orders, or definitive new diagnoses.
- Keep statement text concise (under roughly 180 words total across statements).
PROMPT;
    }

    /**
     * @param list<array{role:string,content:string}> $priorChat
     * @return list<array{role:string,content:string}>
     */
    private function trimChat(array $priorChat): array
    {
        $out = [];
        foreach ($priorChat as $row) {
            if (!is_array($row)) {
                continue;
            }
            $r = isset($row['role']) && is_string($row['role']) ? $row['role'] : '';
            $c = isset($row['content']) && is_string($row['content']) ? $row['content'] : '';
            if ($c === '') {
                continue;
            }
            if ($r !== 'user' && $r !== 'assistant') {
                continue;
            }
            $out[] = ['role' => $r, 'content' => $c];
        }
        if (count($out) > 16) {
            $out = array_values(array_slice($out, -16));
        }
        return $out;
    }

    /**
     * @param array<string,int> $usage
     * @return array<string,mixed>
     */
    private function usageCtx(array $usage, string $model): array
    {
        return [
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'model' => $model,
            'estimated_usd' => OpenAiClient::estimateCostUsd($model, $usage),
        ];
    }

    /**
     * @param list<array<string,mixed>> $messages
     */
    private function messagesPreviewForObs(array $messages): string
    {
        $enc = json_encode($messages, JSON_UNESCAPED_SLASHES);
        return is_string($enc) ? $enc : '';
    }

    /**
     * @param array<string,mixed> $assistantMsg
     */
    private function assistantMessagePreviewForObs(array $assistantMsg): string
    {
        $enc = json_encode($assistantMsg, JSON_UNESCAPED_SLASHES);
        return is_string($enc) ? $enc : '';
    }
}
