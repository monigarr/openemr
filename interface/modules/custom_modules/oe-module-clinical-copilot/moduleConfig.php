<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file moduleConfig.php
 *
 * Module metadata array consumed by OpenEMR Administration → System → Modules (installer/registry).
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 * Copyright (c) 2026 Monica Peters <monigarr@monigarr.com>
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Edit `globals`, `require`, and descriptive fields here; keep `version` in sync with `version.php`.
 *
 * Usage example (integrator):
 * Merge this folder under `interface/modules/custom_modules/oe-module-clinical-copilot/`, then
 * register/enable the module in Admin → System → Modules — OpenEMR reads this file at install time.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md version.php openemr.bootstrap.php
 */

return [
    'name' => 'Clinical Co-Pilot (AgentForge)',
    'description' => 'AI-assisted inter-visit briefing on the patient dashboard with citation-backed output, eval hooks, and structured telemetry.',
    'version' => '0.1.0',
    'author' => 'Monica Peters <monigarr@monigarr.com> GauntletAI.com',
    'license' => 'GPL-3.0',
    'acl_category' => 'patients',
    'acl_section' => 'demo',
    'require' => [
        'openemr' => '>=7.0.0',
    ],
    'globals' => [
        [
            'name' => 'clinical_copilot_enable',
            'type' => 'bool',
            'default' => '1',
            'description' => 'Enable Clinical Co-Pilot card on patient summary',
        ],
        [
            'name' => 'clinical_copilot_openai_model',
            'type' => 'text',
            'default' => 'gpt-4o-mini',
            'description' => 'OpenAI chat model id (e.g. gpt-4o-mini)',
        ],
        [
            'name' => 'clinical_copilot_langfuse_enable',
            'type' => 'bool',
            'default' => '0',
            'description' => 'Allow Langfuse observability export when LANGFUSE_PUBLIC_KEY and LANGFUSE_SECRET_KEY are set (metadata-first; see module README)',
        ],
    ],
];
