<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Langfuse batch ingestion via legacy POST /api/public/ingestion (Guzzle). Fail-open.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class LangfuseCopilotObservability implements CopilotObservabilityPort
{
    /** @var list<array<string,mixed>> */
    private array $batch = [];

    private ?string $traceId = null;

    public function __construct(
        private readonly string $publicKey,
        private readonly string $secretKey,
        private readonly string $baseUrl,
        /** @var 'none'|'redacted' */
        private readonly string $ioMode,
        private readonly int $maxIoChars,
        private readonly LoggerInterface $logger,
        private readonly ?string $release = null,
        private readonly ?string $environment = null,
        private readonly ?ClientInterface $httpClient = null,
    ) {
    }

    public function beginTrace(string $traceId, CopilotTraceContext $context): void
    {
        $this->traceId = $traceId;
        $now = $this->nowIso();
        $body = [
            'id' => $traceId,
            'name' => 'clinical-copilot-' . $context->httpAction,
            'timestamp' => $now,
            'userId' => $context->userIdOpaque,
            'sessionId' => $context->sessionIdOpaque,
            'tags' => ['clinical-copilot', $context->httpAction],
            'input' => [
                'feature' => 'clinical-copilot',
                'http_action' => $context->httpAction,
                'prior_chat_turns' => $context->priorChatTurnCount,
                'user_message_chars' => $context->userLineCharCount,
                'pid_present' => $context->pidPresent,
            ],
            'metadata' => [
                'component' => 'clinical_copilot',
                'http_action' => $context->httpAction,
                'pid_present' => $context->pidPresent,
            ],
        ];
        if ($this->release !== null && $this->release !== '') {
            $body['release'] = $this->release;
        }
        if ($this->environment !== null && $this->environment !== '') {
            $body['environment'] = $this->environment;
        }
        $this->enqueue([
            'id' => Uuid::uuid4()->toString(),
            'timestamp' => $now,
            'type' => 'trace-create',
            'body' => $body,
        ]);
    }

    public function recordOpenAiGeneration(
        string $label,
        string $model,
        float $startMicrotime,
        float $endMicrotime,
        array $usage,
        ?string $inputPreview,
        ?string $outputPreview,
    ): void {
        if ($this->traceId === null) {
            return;
        }

        $input = null;
        $output = null;
        if ($this->ioMode === 'redacted') {
            if ($inputPreview !== null && $inputPreview !== '') {
                $input = TelemetryText::clipForLog($inputPreview, $this->maxIoChars);
            }
            if ($outputPreview !== null && $outputPreview !== '') {
                $output = TelemetryText::clipForLog($outputPreview, $this->maxIoChars);
            }
        }

        $genId = Uuid::uuid4()->toString();
        $startIso = $this->microToIso($startMicrotime);
        $endIso = $this->microToIso($endMicrotime);

        $createBody = [
            'id' => $genId,
            'traceId' => $this->traceId,
            'name' => $label,
            'startTime' => $startIso,
            'model' => $model,
        ];
        if ($input !== null) {
            $createBody['input'] = $input;
        }

        $this->enqueue([
            'id' => Uuid::uuid4()->toString(),
            'timestamp' => $startIso,
            'type' => 'generation-create',
            'body' => $createBody,
        ]);

        $updateBody = [
            'id' => $genId,
            'traceId' => $this->traceId,
            'endTime' => $endIso,
            'usage' => $this->openAiUsage($usage),
        ];
        if ($output !== null) {
            $updateBody['output'] = $output;
        }

        $this->enqueue([
            'id' => Uuid::uuid4()->toString(),
            'timestamp' => $endIso,
            'type' => 'generation-update',
            'body' => $updateBody,
        ]);
    }

    public function recordSpan(string $name, float $startMicrotime, float $endMicrotime, bool $ok, ?string $detail, array $metadata = []): void
    {
        if ($this->traceId === null) {
            return;
        }

        $spanId = Uuid::uuid4()->toString();
        $meta = array_merge($metadata, [
            'ok' => $ok,
            'detail' => $detail !== null ? TelemetryText::clipForLog($detail, 500) : null,
        ]);

        $this->enqueue([
            'id' => Uuid::uuid4()->toString(),
            'timestamp' => $this->microToIso($startMicrotime),
            'type' => 'span-create',
            'body' => [
                'id' => $spanId,
                'traceId' => $this->traceId,
                'name' => $name,
                'startTime' => $this->microToIso($startMicrotime),
                'endTime' => $this->microToIso($endMicrotime),
                'metadata' => $meta,
            ],
        ]);
    }

    public function finalizeTrace(array $metadata): void
    {
        if ($this->traceId === null) {
            return;
        }

        $body = [
            'id' => $this->traceId,
            'metadata' => $metadata,
        ];
        $output = $this->traceOutputSummary($metadata);
        if ($output !== []) {
            $body['output'] = $output;
        }
        $this->enqueue([
            'id' => Uuid::uuid4()->toString(),
            'timestamp' => $this->nowIso(),
            'type' => 'trace-create',
            'body' => $body,
        ]);
    }

    public function flush(): void
    {
        if ($this->batch === []) {
            return;
        }

        $payload = $this->batch;
        $this->batch = [];

        try {
            $client = $this->httpClient ?? new Client([
                'base_uri' => rtrim($this->baseUrl, '/') . '/',
                'timeout' => 5.0,
                'connect_timeout' => 2.0,
                'auth' => [$this->publicKey, $this->secretKey],
            ]);
            $client->post('api/public/ingestion', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['batch' => $payload],
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('clinical_copilot_langfuse_flush_failed', ['exception' => $e]);
        }
    }

    /**
     * @param array<string,mixed> $event
     */
    private function enqueue(array $event): void
    {
        $this->batch[] = $event;
    }

    private function nowIso(): string
    {
        return $this->microToIso(microtime(true));
    }

    private function microToIso(float $micro): string
    {
        $sec = (int) floor($micro);
        $fracMs = (int) round(($micro - $sec) * 1000);
        if ($fracMs >= 1000) {
            $sec++;
            $fracMs -= 1000;
        }
        return gmdate('Y-m-d\TH:i:s', $sec) . sprintf('.%03dZ', $fracMs);
    }

    /**
     * @param array<string,int> $usage
     * @return array<string,int>
     */
    private function openAiUsage(array $usage): array
    {
        return [
            'promptTokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completionTokens' => (int) ($usage['completion_tokens'] ?? 0),
            'totalTokens' => (int) ($usage['total_tokens'] ?? 0),
        ];
    }

    /**
     * Terminal trace fields for Langfuse UI (no PHI); mirrors orchestrator finalize metadata keys.
     *
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    private function traceOutputSummary(array $metadata): array
    {
        $keys = [
            'outcome',
            'error',
            'fallback_premerged',
            'model',
            'estimated_usd',
            'prompt_tokens',
            'completion_tokens',
            'total_tokens',
            'statements_for_ui_count',
            'stripped_count',
            'domain_stripped_count',
        ];
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $metadata)) {
                $out[$k] = $metadata[$k];
            }
        }
        return $out;
    }
}
