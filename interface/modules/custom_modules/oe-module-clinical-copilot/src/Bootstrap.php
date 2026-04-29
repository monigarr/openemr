<?php

/**
 * Event wiring: patient summary section + Twig paths + optional globals.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Patient\Summary\Card\SectionEvent;
use OpenEMR\Services\Globals\GlobalSetting;
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
        if (isset($meta['Portal']['clinical_copilot_openai_api_key'])) {
            return;
        }
        $service->appendToSection(
            'Portal',
            'clinical_copilot_openai_api_key',
            new GlobalSetting(
                xl('Clinical Co-Pilot OpenAI API key'),
                GlobalSetting::DATA_TYPE_PASS,
                '',
                xl('Optional if CLINICAL_COPILOT_OPENAI_API_KEY or OPENAI_API_KEY is set in the server environment.'),
                false
            )
        );
    }
}
