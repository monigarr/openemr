<?php

/**
 * AJAX endpoint for Clinical Co-Pilot briefing.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../../globals.php');

use OpenEMR\Modules\ClinicalCopilot\Controller\CopilotRequestController;

$controller = new CopilotRequestController();
$controller->handle();
