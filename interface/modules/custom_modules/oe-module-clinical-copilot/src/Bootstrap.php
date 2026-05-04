<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file Bootstrap.php
 *
 * Event wiring: patient summary card section, Twig template path, and optional globals registration.
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Instantiated from `openemr.bootstrap.php`; subscribe listeners once per request when the module loads.
 *
 * Usage example (integrator):
 * Fork this class only if you need additional OpenEMR events; keep `subscribeToEvents()` idempotent patterns.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md openemr.bootstrap.php ClinicalCopilotCard.php
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Patient\Summary\Card\SectionEvent;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Services\Globals\GlobalsService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

final class Bootstrap
{
    private readonly string $moduleDir;

    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
        $this->moduleDir = dirname(__DIR__);
    }

    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addListener(SectionEvent::EVENT_HANDLE, $this->addCopilotCard(...), 100);
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, $this->addTwigPath(...));
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, $this->registerGlobals(...));
    }

    public function addCopilotCard(SectionEvent $event): void
    {
        if ($event->getSection() !== 'primary') {
            return;
        }
        if (!OEGlobalsBag::getInstance()->getBoolean('clinical_copilot_enable', true)) {
            return;
        }
        try {
            $event->addCard(new ClinicalCopilotCard(), 0);
        } catch (\Throwable $e) {
            // Duplicate identifier if listener ever double-fired
            if (str_contains($e->getMessage(), 'not unique')) {
                return;
            }
            throw $e;
        }
    }

    public function addTwigPath(TwigEnvironmentEvent $event): void
    {
        try {
            $twig = $event->getTwigEnvironment();
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->moduleDir . DIRECTORY_SEPARATOR . 'templates');
            }
        } catch (LoaderError $e) {
            // Non-fatal: card render would fail without templates; log once
            error_log('ClinicalCopilot: twig path ' . $e->getMessage());
        }
    }

    public function registerGlobals(GlobalsInitializedEvent $event): void
    {
        $service = $event->getGlobalsService();
        $meta = $service->getGlobalsMetadata();
        if (!isset($meta['Portal'])) {
            return;
        }

        $this->appendPortalGlobalIfMissing(
            $service,
            'clinical_copilot_enable',
            new GlobalSetting(
                xl('Clinical Co-Pilot: enable patient summary card'),
                GlobalSetting::DATA_TYPE_BOOL,
                '1',
                xl('Show the Clinical Co-Pilot card on the patient dashboard.'),
                false
            )
        );
        $this->appendPortalGlobalIfMissing(
            $service,
            'clinical_copilot_openai_model',
            new GlobalSetting(
                xl('Clinical Co-Pilot OpenAI model'),
                GlobalSetting::DATA_TYPE_TEXT,
                'gpt-4o-mini',
                xl('OpenAI chat model id (e.g. gpt-4o-mini).'),
                false
            )
        );
        $this->appendPortalGlobalIfMissing(
            $service,
            'clinical_copilot_openai_api_key',
            new GlobalSetting(
                xl('Clinical Co-Pilot OpenAI API key'),
                GlobalSetting::DATA_TYPE_PASS,
                '',
                xl('Optional if CLINICAL_COPILOT_OPENAI_API_KEY or OPENAI_API_KEY is set in the server environment.'),
                false
            )
        );
        $this->appendPortalGlobalIfMissing(
            $service,
            'clinical_copilot_langfuse_enable',
            new GlobalSetting(
                xl('Clinical Co-Pilot: allow Langfuse observability export'),
                GlobalSetting::DATA_TYPE_BOOL,
                '0',
                xl('When enabled and LANGFUSE_PUBLIC_KEY / LANGFUSE_SECRET_KEY are set, export dual-write telemetry to Langfuse (metadata-first; see module README).'),
                false
            )
        );
    }

    private function appendPortalGlobalIfMissing(GlobalsService $service, string $key, GlobalSetting $setting): void
    {
        $portal = $service->getGlobalsMetadata()['Portal'] ?? [];
        if (isset($portal[$key])) {
            return;
        }
        $service->appendToSection('Portal', $key, $setting);
    }
}
