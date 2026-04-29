<?php

/**
 * Module metadata for Admin → System → Modules installer.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

return [
    'name' => 'Clinical Co-Pilot (AgentForge)',
    'description' => 'AI-assisted inter-visit briefing on the patient dashboard with citation-backed output, eval hooks, and structured telemetry.',
    'version' => '0.1.0',
    'author' => 'AgentForge fork',
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
    ],
];
