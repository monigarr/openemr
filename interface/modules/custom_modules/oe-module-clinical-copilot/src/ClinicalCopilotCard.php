<?php

/**
 * Patient summary card: Clinical Co-Pilot panel.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
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
