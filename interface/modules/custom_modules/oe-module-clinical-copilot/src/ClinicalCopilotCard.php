<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file ClinicalCopilotCard.php
 *
 * Patient summary dashboard card model: Clinical Co-Pilot panel metadata, ACL, and AJAX URL wiring.
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Registered via `Bootstrap` on the patient summary section event; renders `summary_card.html.twig`.
 *
 * Usage example (integrator):
 * Change `TEMPLATE` or card options here to alter dashboard placement/labels; keep ACL aligned with `moduleConfig.php`.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md templates/clinical_copilot/summary_card.html.twig public/copilot_request.php
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot;

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Patient\Summary\Card\CardModel;

final class ClinicalCopilotCard extends CardModel
{
    private const TEMPLATE = 'clinical_copilot/summary_card.html.twig';

    public function __construct()
    {
        parent::__construct([
            'acl' => ['patients', 'demo'],
            'initiallyCollapsed' => false,
            'add' => false,
            'edit' => false,
            'collapse' => true,
            'templateFile' => self::TEMPLATE,
            'identifier' => 'clinical_copilot',
            'title' => xl('Clinical Co-Pilot'),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getTemplateVariables(): array
    {
        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        $csrf = CsrfUtils::collectCsrfToken($session, 'default');
        $webroot = OEGlobalsBag::getInstance()->getWebRoot();
        $pid = (int) ($session->get('pid') ?? 0);

        return [
            'card' => $this,
            'auth' => false,
            'copilotAjaxUrl' => $webroot . '/interface/modules/custom_modules/oe-module-clinical-copilot/public/copilot_request.php',
            'csrf' => $csrf,
            'pid' => $pid,
        ];
    }
}
