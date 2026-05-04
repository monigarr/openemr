<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file AgentTelemetry.php
 *
 * Structured, redacted step timing and metadata for Clinical Co-Pilot requests (observability baseline).
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Instantiate per request; `mark()` steps then `flush()` to logging sink with request/correlation context.
 *
 * Usage example (integrator):
 * Wrap new pipeline stages with `mark('stage_name', true/false, 'optional detail')` for consistent JSON logs.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @subpackage Services
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md AgentOrchestrator.php
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use OpenEMR\BC\ServiceContainer;

final class AgentTelemetry
{
    /** @var list<array{step:string,ms:float,ok:bool,detail?:string}> */
    private array $steps = [];

    private float $t0;

    public function __construct()
    {
        $this->t0 = microtime(true);
    }

    public function mark(string $step, bool $ok = true, ?string $detail = null): void
    {
        $this->steps[] = [
            'step' => $step,
            'ms' => round((microtime(true) - $this->t0) * 1000, 2),
            'ok' => $ok,
            'detail' => $detail !== null ? $this->redact($detail) : null,
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    public function flush(string $requestId, int $pid, array $context = []): void
    {
        $payload = [
            'component' => 'clinical_copilot',
            'request_id' => $requestId,
            'pid_present' => $pid > 0,
            'steps' => $this->steps,
        ];
        foreach ($context as $k => $v) {
            if ($k === 'prompt_tokens' || $k === 'completion_tokens' || $k === 'total_tokens' || $k === 'model' || $k === 'estimated_usd') {
                $payload[$k] = $v;
            }
        }
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($line !== false) {
            ServiceContainer::getLogger()->info('clinical_copilot_telemetry', ['json' => $line]);
        }
    }

    private function redact(string $s): string
    {
        return TelemetryText::clipForLog($s, 500);
    }
}
