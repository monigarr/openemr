<?php

/**
 * Clinical Co-Pilot — custom module bootstrap.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
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
