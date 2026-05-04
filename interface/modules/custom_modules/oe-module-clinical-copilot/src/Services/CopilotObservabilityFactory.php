<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Builds the observability port from Globals + environment (default: disabled).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Core\OEGlobalsBag;
use Psr\Log\LoggerInterface;

final class CopilotObservabilityFactory
{
    /**
     * Langfuse requires Admin toggle plus keys unless globally disabled via LANGFUSE_ENABLED.
     *
     * @param OEGlobalsBag|null $globalsBag Override for tests; defaults to the app singleton.
     */
    public static function create(?LoggerInterface $logger = null, ?OEGlobalsBag $globalsBag = null): CopilotObservabilityPort
    {
        $logger ??= ServiceContainer::getLogger();

        $globals = $globalsBag ?? OEGlobalsBag::getInstance();
        if (!$globals->getBoolean('clinical_copilot_langfuse_enable', false)) {
            return new NullCopilotObservability();
        }

        $envFlag = getenv('LANGFUSE_ENABLED');
        if ($envFlag !== false && !self::envTruthy((string) $envFlag)) {
            return new NullCopilotObservability();
        }

        $pk = getenv('LANGFUSE_PUBLIC_KEY');
        $sk = getenv('LANGFUSE_SECRET_KEY');
        if (!is_string($pk) || $pk === '' || !is_string($sk) || $sk === '') {
            return new NullCopilotObservability();
        }

        $base = getenv('LANGFUSE_BASE_URL');
        if (!is_string($base) || $base === '') {
            $host = getenv('LANGFUSE_HOST');
            $base = is_string($host) && $host !== '' ? $host : 'https://cloud.langfuse.com';
        }
        $baseUrl = $base;

        $ioRaw = getenv('LANGFUSE_CLINICAL_COPILOT_IO');
        $ioMode = (is_string($ioRaw) && strtolower(trim($ioRaw)) === 'redacted') ? 'redacted' : 'none';

        $maxCharsRaw = getenv('LANGFUSE_IO_MAX_CHARS');
        $maxChars = is_string($maxCharsRaw) && $maxCharsRaw !== '' ? (int) $maxCharsRaw : 500;
        if ($maxChars < 1) {
            $maxChars = 500;
        }

        $releaseRaw = getenv('LANGFUSE_RELEASE');
        $release = is_string($releaseRaw) && $releaseRaw !== '' ? $releaseRaw : null;
        $envRaw = getenv('LANGFUSE_TRACING_ENVIRONMENT');
        if (!is_string($envRaw) || $envRaw === '') {
            $envRaw = getenv('LANGFUSE_ENV');
        }
        $environment = is_string($envRaw) && $envRaw !== '' ? $envRaw : null;

        return new LangfuseCopilotObservability($pk, $sk, $baseUrl, $ioMode, $maxChars, $logger, $release, $environment);
    }

    private static function envTruthy(string $value): bool
    {
        $v = strtolower(trim($value));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
