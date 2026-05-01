<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file openemr.bootstrap.php
 *
 * Custom module entrypoint: registers PSR-4 namespace and wires `Bootstrap` to the event dispatcher.
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * OpenEMR loads this file when the module is enabled; do not rename. Autoload maps `src/` to
 * `OpenEMR\Modules\ClinicalCopilot\`.
 *
 * Usage example (integrator):
 * Ensure this file exists at `interface/modules/custom_modules/oe-module-clinical-copilot/openemr.bootstrap.php`
 * alongside `src/` and `moduleConfig.php`, then enable the module in Admin → System → Modules.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md moduleConfig.php src/Bootstrap.php
 */

namespace OpenEMR\Modules\ClinicalCopilot;

/**
 * @global \OpenEMR\Core\ModulesClassLoader $classLoader
 * @global \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */

$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\ClinicalCopilot\\',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

$bootstrap = new Bootstrap($eventDispatcher);
$bootstrap->subscribeToEvents();
