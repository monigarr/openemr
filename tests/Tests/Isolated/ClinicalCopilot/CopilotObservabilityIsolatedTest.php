<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Isolated tests for Clinical Co-Pilot Langfuse observability (no HTTP to real Langfuse).
 *
 * @package OpenEMR\Tests
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\ClinicalCopilot;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\ClinicalCopilot\Services\CopilotObservabilityFactory;
use OpenEMR\Modules\ClinicalCopilot\Services\CopilotTraceContext;
use OpenEMR\Modules\ClinicalCopilot\Services\LangfuseCopilotObservability;
use OpenEMR\Modules\ClinicalCopilot\Services\NullCopilotObservability;
use OpenEMR\Modules\ClinicalCopilot\Services\TelemetryText;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CopilotObservabilityIsolatedTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
        }
        $this->previousEnv = [];
        parent::tearDown();
    }

    /**
     * @param non-empty-string $key
     */
    private function stashEnv(string $key): void
    {
        if (!array_key_exists($key, $this->previousEnv)) {
            $v = getenv($key);
            $this->previousEnv[$key] = $v !== false ? $v : false;
        }
    }

    public function testTelemetryTextClipAddsEllipsisWhenTruncating(): void
    {
        $long = str_repeat('a', 600);
        $out = TelemetryText::clipForLog($long, 500);
        // UTF-8 ellipsis is 3 bytes; total output must not exceed maxLength.
        $this->assertSame(500, strlen($out));
        $this->assertStringEndsWith('…', $out);
    }

    public function testFactoryReturnsNullWhenGlobalDisabled(): void
    {
        $globals = new OEGlobalsBag(['clinical_copilot_langfuse_enable' => false]);
        $port = CopilotObservabilityFactory::create(new NullLogger(), $globals);
        $this->assertInstanceOf(NullCopilotObservability::class, $port);
    }

    public function testFactoryReturnsNullWhenLangfuseExplicitlyDisabled(): void
    {
        $this->stashEnv('LANGFUSE_ENABLED');
        putenv('LANGFUSE_ENABLED=0');
        $globals = new OEGlobalsBag(['clinical_copilot_langfuse_enable' => true]);
        $this->stashEnv('LANGFUSE_PUBLIC_KEY');
        $this->stashEnv('LANGFUSE_SECRET_KEY');
        putenv('LANGFUSE_PUBLIC_KEY=pk-test');
        putenv('LANGFUSE_SECRET_KEY=sk-test');

        $port = CopilotObservabilityFactory::create(new NullLogger(), $globals);
        $this->assertInstanceOf(NullCopilotObservability::class, $port);
    }

    public function testFactoryReturnsLangfuseWhenEnabledAndKeysPresent(): void
    {
        $this->stashEnv('LANGFUSE_ENABLED');
        putenv('LANGFUSE_ENABLED=1');
        $this->stashEnv('LANGFUSE_PUBLIC_KEY');
        $this->stashEnv('LANGFUSE_SECRET_KEY');
        putenv('LANGFUSE_PUBLIC_KEY=pk-test');
        putenv('LANGFUSE_SECRET_KEY=sk-test');

        $globals = new OEGlobalsBag(['clinical_copilot_langfuse_enable' => true]);
        $port = CopilotObservabilityFactory::create(new NullLogger(), $globals);
        $this->assertInstanceOf(LangfuseCopilotObservability::class, $port);
    }

    public function testLangfuseFlushSendsBatchJson(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(207, [], '{"successes":[],"errors":[]}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $obs = new LangfuseCopilotObservability(
            'pk-x',
            'sk-y',
            'https://example.langfuse.com',
            'none',
            500,
            new NullLogger(),
            null,
            null,
            $client,
        );

        $obs->beginTrace(
            'abcd1234',
            new CopilotTraceContext('user-hash', 'sess-hash', 'brief', true, 2, 0),
        );
        $t0 = microtime(true);
        $obs->recordOpenAiGeneration(
            'openai_round_0',
            'gpt-4o-mini',
            $t0,
            $t0 + 0.01,
            ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
            'should-not-appear-in-metadata-mode',
            'also-hidden',
        );
        $obs->finalizeTrace(['outcome' => 'ok']);
        $obs->flush();

        $this->assertCount(1, $container);
        /** @var array{request:\GuzzleHttp\Psr7\Request} $first */
        $first = $container[0];
        $body = (string) $first['request']->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('batch', $decoded);
        $batch = $decoded['batch'];
        $this->assertIsArray($batch);

        $types = [];
        foreach ($batch as $ev) {
            $this->assertIsArray($ev);
            $types[] = $ev['type'] ?? null;
        }
        $this->assertContains('trace-create', $types, '', true);
        $this->assertContains('generation-create', $types, '', true);
        $this->assertContains('generation-update', $types, '', true);

        foreach ($batch as $ev) {
            if (($ev['type'] ?? '') === 'trace-create') {
                $tb = $ev['body'] ?? [];
                $this->assertIsArray($tb);
                if (($tb['id'] ?? '') === 'abcd1234' && isset($tb['tags'])) {
                    $this->assertSame(['clinical-copilot', 'brief'], $tb['tags']);
                    $this->assertSame('clinical-copilot-brief', $tb['name']);
                    $this->assertSame(
                        [
                            'feature' => 'clinical-copilot',
                            'http_action' => 'brief',
                            'prior_chat_turns' => 2,
                            'user_message_chars' => 0,
                            'pid_present' => true,
                        ],
                        $tb['input'],
                    );
                }
                if (($tb['id'] ?? '') === 'abcd1234' && isset($tb['output'])) {
                    $this->assertSame('ok', $tb['output']['outcome'] ?? null);
                }
            }
            if (($ev['type'] ?? '') === 'generation-create') {
                $bodyInner = $ev['body'] ?? [];
                $this->assertIsArray($bodyInner);
                $this->assertArrayNotHasKey('input', $bodyInner);
                $this->assertArrayNotHasKey('output', $bodyInner);
            }
        }
    }

    public function testLangfuseRedactedModeTruncatesIo(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(207, [], '{"successes":[],"errors":[]}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $obs = new LangfuseCopilotObservability(
            'pk-x',
            'sk-y',
            'https://example.langfuse.com',
            'redacted',
            120,
            new NullLogger(),
            null,
            null,
            $client,
        );

        $obs->beginTrace(
            'trace99',
            new CopilotTraceContext('u', 's', 'message', true, 0, 42),
        );
        $longIn = str_repeat('Z', 400);
        $longOut = str_repeat('Y', 400);
        $t0 = microtime(true);
        $obs->recordOpenAiGeneration(
            'gen',
            'gpt-4o-mini',
            $t0,
            $t0 + 0.001,
            ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            $longIn,
            $longOut,
        );
        $obs->flush();

        $this->assertCount(1, $container);
        /** @var array{request:\GuzzleHttp\Psr7\Request} $first */
        $first = $container[0];
        $decoded = json_decode((string) $first['request']->getBody(), true);
        $this->assertIsArray($decoded);
        foreach ($decoded['batch'] as $ev) {
            if (($ev['type'] ?? '') === 'generation-create') {
                $in = $ev['body']['input'] ?? '';
                $this->assertIsString($in);
                $this->assertLessThanOrEqual(120, strlen($in));
                $this->assertStringEndsWith('…', $in);
            }
            if (($ev['type'] ?? '') === 'generation-update') {
                $out = $ev['body']['output'] ?? '';
                $this->assertIsString($out);
                $this->assertLessThanOrEqual(120, strlen($out));
                $this->assertStringEndsWith('…', $out);
            }
        }
    }
}
