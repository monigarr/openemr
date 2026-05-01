<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file version.php
 *
 * Semantic version components for the Clinical Co-Pilot custom module (OpenEMR installer).
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Keep `$v_major`, `$v_minor`, `$v_patch`, `$v_tag`, and `$v_database` aligned with
 * `moduleConfig.php` `version` and release notes when you ship updates.
 *
 * Usage example (integrator):
 * After changing these values, bump `moduleConfig.php` `version` and reinstall/upgrade
 * the module from Administration → System → Modules if your workflow requires it.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md moduleConfig.php
 */

$v_major = '0';
$v_minor = '1';
$v_patch = '0';
$v_tag   = 'agentforge-week1';
$v_database = 0;
