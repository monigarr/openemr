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
use OpenEMR\Modules\ClinicalCopilot\Services\CopilotObservabilityFactory;
use OpenEMR\Modules\ClinicalCopilot\Services\CopilotRunInstrumentation;
use OpenEMR\Modules\ClinicalCopilot\Services\CopilotTraceContext;
use OpenEMR\Modules\ClinicalCopilot\Services\CitationLabelBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\ClinicalDomainRules;
use OpenEMR\Modules\ClinicalCopilot\Services\ConversationStore;
use OpenEMR\Modules\ClinicalCopilot\Services\DisplaySanitizer;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\DocumentExtractionPipelineFactory;
use OpenEMR\Modules\ClinicalCopilot\Services\Document\SessionDocumentExtractionsStore;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirDocumentReferenceDraftBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\Fhir\FhirObservationDraftBuilder;
use OpenEMR\Modules\ClinicalCopilot\Services\Persistence\CopilotChartDocumentPersister;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\CohereReranker;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\GuidelineChunkRepository;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\HybridGuidelineRetriever;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\PassThroughReranker;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\RerankerInterface;
use OpenEMR\Modules\ClinicalCopilot\Services\OpenAiClient;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\AttachAndExtractTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ChartListsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\DocumentExtractionsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentEncountersTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RecentLabsTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\RetrieveGuidelinesTool;
use OpenEMR\Modules\ClinicalCopilot\Services\Tools\ToolRegistry;
use OpenEMR\Modules\ClinicalCopilot\Services\VerificationGate;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
        if (!in_array($action, ['brief', 'message', 'reset', 'upload_document'], true)) {
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

        if ($action === 'upload_document') {
            $docType = isset($_POST['doc_type']) && is_string($_POST['doc_type']) ? trim($_POST['doc_type']) : '';
            if (!in_array($docType, ['lab_pdf', 'intake_form'], true)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'invalid_doc_type', 'request_id' => $requestId]);
                return;
            }
            $file = $_FILES['clinical_copilot_file'] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'upload_failed', 'request_id' => $requestId]);
                return;
            }
            $maxBytes = 15 * 1024 * 1024;
            $size = (int) ($file['size'] ?? 0);
            if ($size < 1 || $size > $maxBytes) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'file_too_large', 'request_id' => $requestId]);
                return;
            }
            $tmpName = $file['tmp_name'] ?? '';
            if (!is_string($tmpName) || !is_uploaded_file($tmpName)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'upload_invalid', 'request_id' => $requestId]);
                return;
            }
            $mime = isset($file['type']) && is_string($file['type']) ? $file['type'] : '';
            if ($mime !== 'application/pdf') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'pdf_only_demo', 'request_id' => $requestId]);
                return;
            }
            $dest = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ccop_' . bin2hex(random_bytes(8)) . '.pdf';
            if (!@move_uploaded_file($tmpName, $dest)) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => 'store_failed', 'request_id' => $requestId]);
                return;
            }
            $orig = isset($file['name']) && is_string($file['name']) ? $file['name'] : 'upload.pdf';
            $docStore = new SessionDocumentExtractionsStore($session);
            $docStore->setPending($pid, [
                'doc_type' => $docType,
                'path' => $dest,
                'original_filename' => $orig,
            ]);
            $chartDocId = null;
            if (CopilotChartDocumentPersister::isEnabled()) {
                $persister = new CopilotChartDocumentPersister();
                $pRes = $persister->persistPatientPdf($pid, $dest, $orig);
                if ($pRes['ok'] && isset($pRes['doc_id'])) {
                    $chartDocId = $pRes['doc_id'];
                    $docStore->setLastChartDocumentId($pid, $chartDocId);
                }
            }
            $uploadPayload = [
                'ok' => true,
                'action' => 'upload_document',
                'doc_type' => $docType,
                'request_id' => $requestId,
            ];
            if ($chartDocId !== null) {
                $uploadPayload['chart_document_id'] = $chartDocId;
            }
            echo json_encode($uploadPayload);
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
            $payload = $this->runOrchestrator($session, $pid, $requestId, $prior, $userMessage, 'message', $conversationStore, $authUser);
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
        $payload = $this->runOrchestrator($session, $pid, $requestId, $prior, '', 'brief', $conversationStore, $authUser);
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
    private function runOrchestrator(
        SessionInterface $session,
        int $pid,
        string $requestId,
        array $prior,
        string $userLine,
        string $httpAction,
        ConversationStore $conversationStore,
        string $authUser,
    ): array {
        $saltRaw = getenv('LANGFUSE_ID_SALT');
        $salt = is_string($saltRaw) ? $saltRaw : '';
        $userOpaque = hash('sha256', $authUser . "\0" . $salt);
        $convTok = $conversationStore->getConversationToken() ?? '';
        $sessionOpaque = hash('sha256', $authUser . "\0" . $convTok . "\0" . $pid . "\0" . $salt);

        $traceCtx = new CopilotTraceContext(
            $userOpaque,
            $sessionOpaque,
            $httpAction,
            $pid > 0,
            count($prior),
            strlen($userLine),
        );
        $obs = CopilotObservabilityFactory::create();
        $instr = new CopilotRunInstrumentation($obs, $traceCtx, $httpAction);

        try {
            $apiKey = $this->resolveApiKey();
            $orchestrator = new AgentOrchestrator(
                $this->createToolRegistry($session),
                new OpenAiClient($apiKey),
                new VerificationGate(),
                new ClinicalDomainRules(),
                new CitationLabelBuilder(),
                new DisplaySanitizer(),
            );
            $telemetry = new AgentTelemetry();
            if ($userLine === '') {
                return $orchestrator->runBriefing($pid, $telemetry, $requestId, $prior, '', $instr);
            }
            return $orchestrator->runAgentTurn($pid, $telemetry, $requestId, $prior, $userLine, $instr);
        } finally {
            $obs->flush();
        }
    }

    private function resolveApiKey(): ?string
    {
        // Prefer Globals when set so a repo-root .env (e.g. dev-easy env_file) with a wrong or template
        // OPENAI_API_KEY does not override the key saved in Admin → Config → Portal.
        $g = trim(OEGlobalsBag::getInstance()->getString('clinical_copilot_openai_api_key'));
        if ($g !== '') {
            return $g;
        }
        $fromEnv = getenv('CLINICAL_COPILOT_OPENAI_API_KEY');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }
        $fromEnv2 = getenv('OPENAI_API_KEY');
        if (is_string($fromEnv2) && $fromEnv2 !== '') {
            return $fromEnv2;
        }

        return null;
    }

    private function createReranker(): RerankerInterface
    {
        $k = getenv('CLINICAL_COPILOT_COHERE_API_KEY');
        if (is_string($k) && $k !== '') {
            return new CohereReranker($k);
        }
        return new PassThroughReranker();
    }

    private function createToolRegistry(SessionInterface $session): ToolRegistry
    {
        $docStore = new SessionDocumentExtractionsStore($session);
        $pipeline = DocumentExtractionPipelineFactory::createDefault();
        $retriever = new HybridGuidelineRetriever(new GuidelineChunkRepository(), $this->createReranker());
        return new ToolRegistry(
            new ChartListsTool(new ChartContextTool()),
            new RecentEncountersTool(),
            new RecentLabsTool(),
            new DocumentExtractionsTool($docStore),
            new RetrieveGuidelinesTool($retriever),
            new AttachAndExtractTool(
                $docStore,
                $pipeline,
                new FhirObservationDraftBuilder(),
                new FhirDocumentReferenceDraftBuilder(),
            ),
        );
    }
}
