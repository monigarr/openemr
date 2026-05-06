<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file copilot_request.php
 *
 * Public web endpoint: POST JSON briefing for the Clinical Co-Pilot card (delegates to controller).
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Invoked from the patient-summary Twig card via same-origin `fetch` POST with CSRF token;
 * requires an authenticated session and active patient context.
 *
 * Usage example (integrator):
 * POST to this script’s URL with `csrf_token_form` (same pattern as core OpenEMR forms); do not pass
 * patient id in the body for authorization — the controller binds to session `pid` only.
 *
 * Security: Trust boundary uses the active OpenEMR session `pid` only; never honor a client-supplied
 * patient identifier for chart or model context.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md src/Controller/CopilotRequestController.php
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../../globals.php');

use OpenEMR\Modules\ClinicalCopilot\Controller\CopilotRequestController;

try {
    $controller = new CopilotRequestController();
    $controller->handle();
} catch (\Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
    }
    error_log('ClinicalCopilot copilot_request: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
