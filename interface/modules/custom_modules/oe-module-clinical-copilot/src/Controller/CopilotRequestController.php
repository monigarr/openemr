<?php

/**
 * AJAX handler: CSRF + session PID binding + orchestration.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
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
use OpenEMR\Modules\ClinicalCopilot\Services\OpenAiClient;
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
        // Ignore any client-supplied pid (IDOR hardening)
        $requestId = bin2hex(random_bytes(8));

        $apiKey = $this->resolveApiKey();
        $orchestrator = new AgentOrchestrator(
            new ChartContextTool(),
            new OpenAiClient($apiKey),
            new VerificationGate()
        );

        $telemetry = new AgentTelemetry();
        $result = $orchestrator->runBriefing($pid, $telemetry, $requestId);

        if (!($result['ok'] ?? false)) {
            $code = ($result['error'] ?? '') === 'not_logged_in' ? 401 : 200;
            http_response_code($code);
            echo json_encode([
                'ok' => false,
                'error' => $result['error'] ?? 'unknown',
                'request_id' => $requestId,
            ]);
            return;
        }

        echo json_encode([
            'ok' => true,
            'text' => $result['text'] ?? '',
            'request_id' => $requestId,
            'usage' => $result['usage'] ?? [],
            'model' => $result['model'] ?? '',
            'estimated_usd' => $result['estimated_usd'] ?? 0.0,
        ]);
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
