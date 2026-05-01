<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file CopilotRequestController.php
 *
 * AJAX controller: CSRF, ACL, session pid, multi-turn conversation, orchestration, JSON response.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Controller;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\ClinicalCopilot\Services\AgentOrchestrator;
use OpenEMR\Modules\ClinicalCopilot\Services\AgentTelemetry;
use OpenEMR\Modules\ClinicalCopilot\Services\ChartContextTool;
use OpenEMR\Modules\ClinicalCopilot\Services\CitationLabelBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\ClinicalDomainRules;
use OpenEMR\Modules\ClinicalCopilot\Services\ConversationStore;
use OpenEMR\Modules\ClinicalCopilot\Services\DisplaySanitizer;
use OpenEMR\Modules\ClinicalCopilot\Services\OpenAiClient;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ChartListsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentEncountersTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentLabsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ToolRegistry;
use OpenEMR\Modules\ClinicalCopilot\Services\VerificationGate;

final class CopilotRequestController
{
    public function handle(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        if ($session->get('authUser') === null || $session->get('authUser') === '') {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
            return;
        }

        try {
            CsrfUtils::checkCsrfInput(INPUT_POST, $session, 'csrf_token_form', 'default', false);
        } catch (\OpenEMR\Common\Csrf\CsrfInvalidException $e) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        if (!AclMain::aclCheckCore('patients', 'demo')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'acl']);
            return;
        }

        $pid = (int) ($session->get('pid') ?? 0);
        $requestId = bin2hex(random_bytes(8));
        $authUser = (string) $session->get('authUser');

        $rawAction = $_POST['clinical_copilot_action'] ?? $_POST['action'] ?? 'brief';
        $action = strtolower(trim((string) $rawAction));
        if (!in_array($action, ['brief', 'message', 'reset'], true)) {
            $action = 'brief';
        }

        $conversationStore = new ConversationStore($session);

        if ($action === 'reset') {
            $conversationStore->reset();
            echo json_encode([
                'ok' => true,
                'action' => 'reset',
                'request_id' => $requestId,
                'conversation_token' => null,
                'messages' => [],
            ]);
            return;
        }

        if ($pid < 1) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'no_active_patient', 'request_id' => $requestId]);
            return;
        }

        if ($action === 'message') {
            $token = isset($_POST['conversation_token']) && is_string($_POST['conversation_token'])
                ? trim($_POST['conversation_token']) : '';
            if (!$conversationStore->validate($authUser, $pid, $token !== '' ? $token : null)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'invalid_conversation', 'request_id' => $requestId]);
                return;
            }
            $userMessage = isset($_POST['user_message']) && is_string($_POST['user_message'])
                ? trim($_POST['user_message']) : '';
            if ($userMessage === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'empty_message', 'request_id' => $requestId]);
                return;
            }
            $prior = $conversationStore->getMessages();
            $payload = $this->runOrchestrator($pid, $requestId, $prior, $userMessage);
            if (!($payload['ok'] ?? false)) {
                http_response_code(200);
                echo json_encode(array_merge($payload, ['request_id' => $requestId]));
                return;
            }
            $conversationStore->appendMessage('user', $userMessage);
            $conversationStore->appendMessage('assistant', (string) ($payload['text'] ?? ''));
            $convToken = $conversationStore->getConversationToken() ?? '';
            echo json_encode(array_merge($payload, [
                'request_id' => $requestId,
                'conversation_token' => $convToken,
                'messages' => $conversationStore->getMessages(),
            ]));
            return;
        }

        // brief (default): backward-compatible when only CSRF is posted
        $conversationStore->getOrCreateToken($authUser, $pid);
        $prior = $conversationStore->getMessages();
        $payload = $this->runOrchestrator($pid, $requestId, $prior, '');
        if (!($payload['ok'] ?? false)) {
            http_response_code(200);
            echo json_encode(array_merge($payload, ['request_id' => $requestId]));
            return;
        }
        $conversationStore->appendMessage('user', 'Briefing request');
        $conversationStore->appendMessage('assistant', (string) ($payload['text'] ?? ''));
        $convToken = $conversationStore->getConversationToken() ?? '';
        echo json_encode(array_merge($payload, [
            'request_id' => $requestId,
            'conversation_token' => $convToken,
            'messages' => $conversationStore->getMessages(),
        ]));
    }

    /**
     * @param list<array{role:string,content:string}> $prior
     * @return array<string,mixed>
     */
    private function runOrchestrator(int $pid, string $requestId, array $prior, string $userLine): array
    {
        $apiKey = $this->resolveApiKey();
        $orchestrator = new AgentOrchestrator(
            new ToolRegistry(
                new ChartListsTool(new ChartContextTool()),
                new RecentEncountersTool(),
                new RecentLabsTool(),
            ),
            new OpenAiClient($apiKey),
            new VerificationGate(),
            new ClinicalDomainRules(),
            new CitationLabelBuilder(),
            new DisplaySanitizer(),
        );
        $telemetry = new AgentTelemetry();
        if ($userLine === '') {
            return $orchestrator->runBriefing($pid, $telemetry, $requestId, $prior, '');
        }
        return $orchestrator->runAgentTurn($pid, $telemetry, $requestId, $prior, $userLine);
    }

    private function resolveApiKey(): ?string
    {
        $fromEnv = getenv('CLINICAL_COPILOT_OPENAI_API_KEY');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }
        $fromEnv2 = getenv('OPENAI_API_KEY');
        if (is_string($fromEnv2) && $fromEnv2 !== '') {
            return $fromEnv2;
        }
        $g = OEGlobalsBag::getInstance()->getString('clinical_copilot_openai_api_key');
        return ($g !== '') ? $g : null;
    }
}
